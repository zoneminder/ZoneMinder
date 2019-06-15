<?php
//
// ZoneMinder web action file
// Copyright (C) 2019 ZoneMinder LLC
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

// Monitor edit actions, monitor id derived, require edit permissions for that monitor
if ( ! canEdit('Monitors') ) {
  ZM\Warning('Monitor actions require Monitors Permissions');
  return;
}

if ( $action == 'monitor' ) {
  $mid = 0;
  if ( !empty($_REQUEST['mid']) ) {
    $mid = validInt($_REQUEST['mid']);
    $monitor = dbFetchOne('SELECT * FROM Monitors WHERE Id=?', NULL, array($mid));

    if ( ZM_OPT_X10 ) {
      $x10Monitor = dbFetchOne('SELECT * FROM TriggersX10 WHERE MonitorId=?', NULL, array($mid));
      if ( !$x10Monitor )
        $x10Monitor = array();
    }
  } else {
    $monitor = array();
    if ( ZM_OPT_X10 ) {
      $x10Monitor = array();
    }
  }
  $Monitor = new ZM\Monitor($monitor);

  // Define a field type for anything that's not simple text equivalent
  $types = array(
      'Triggers' => 'set',
      'Controllable' => 'toggle',
      'TrackMotion' => 'toggle',
      'Enabled' => 'toggle',
      'DoNativeMotDet' => 'toggle',
      'Exif' => 'toggle',
      'RTSPDescribe' => 'toggle',
      'RecordAudio' => 'toggle',
      'Method' => 'raw',
      );

  if ( $_REQUEST['newMonitor']['ServerId'] == 'auto' ) {
    $_REQUEST['newMonitor']['ServerId'] = dbFetchOne(
      'SELECT Id FROM Servers WHERE Status=\'Running\' ORDER BY FreeMem DESC, CpuLoad ASC LIMIT 1', 'Id');
    ZM\Logger::Debug('Auto selecting server: Got ' . $_REQUEST['newMonitor']['ServerId']);
    if ( ( !$_REQUEST['newMonitor'] ) and defined('ZM_SERVER_ID') ) {
      $_REQUEST['newMonitor']['ServerId'] = ZM_SERVER_ID;
      ZM\Logger::Debug('Auto selecting server to ' . ZM_SERVER_ID);
    }
  }

  $columns = getTableColumns('Monitors');
  $changes = getFormChanges($monitor, $_REQUEST['newMonitor'], $types, $columns);
#ZM\Logger::Debug("Columns:". print_r($columns,true));
#ZM\Logger::Debug("Changes:". print_r($changes,true));
#ZM\Logger::Debug("newMonitor:". print_r($_REQUEST['newMonitor'],true));

  if ( count($changes) ) {
    if ( $mid ) {

      # If we change anything that changes the shared mem size, zma can complain.  So let's stop first.
      if ( $monitor['Type'] != 'WebSite' ) {
        $Monitor->zmaControl('stop');
        $Monitor->zmcControl('stop');
      }
      dbQuery('UPDATE Monitors SET '.implode(', ', $changes).' WHERE Id=?', array($mid));
      // Groups will be added below
      if ( isset($changes['Name']) or isset($changes['StorageId']) ) {
        $OldStorage = new ZM\Storage($monitor['StorageId']);
        $saferOldName = basename($monitor['Name']);
        if ( file_exists($OldStorage->Path().'/'.$saferOldName) )
          unlink($OldStorage->Path().'/'.$saferOldName);

        $NewStorage = new ZM\Storage($_REQUEST['newMonitor']['StorageId']);
        if ( !file_exists($NewStorage->Path().'/'.$mid) ) {
          if ( !mkdir($NewStorage->Path().'/'.$mid, 0755) ) {
            ZM\Error('Unable to mkdir ' . $NewStorage->Path().'/'.$mid);
          }
        }
        $saferNewName = basename($_REQUEST['newMonitor']['Name']);
        if ( !symlink($NewStorage->Path().'/'.$mid, $NewStorage->Path().'/'.$saferNewName) ) {
          ZM\Warning('Unable to symlink ' . $NewStorage->Path().'/'.$mid . ' to ' . $NewStorage->Path().'/'.$saferNewName);
        }
      }
      if ( isset($changes['Width']) || isset($changes['Height']) ) {
        $newW = $_REQUEST['newMonitor']['Width'];
        $newH = $_REQUEST['newMonitor']['Height'];
        $newA = $newW * $newH;
        $oldW = $monitor['Width'];
        $oldH = $monitor['Height'];
        $oldA = $oldW * $oldH;

        $zones = dbFetchAll('SELECT * FROM Zones WHERE MonitorId=?', NULL, array($mid));
        foreach ( $zones as $zone ) {
          $newZone = $zone;
          $points = coordsToPoints($zone['Coords']);
          for ( $i = 0; $i < count($points); $i++ ) {
            $points[$i]['x'] = intval(($points[$i]['x']*($newW-1))/($oldW-1));
            $points[$i]['y'] = intval(($points[$i]['y']*($newH-1))/($oldH-1));
          }
          $newZone['Coords'] = pointsToCoords($points);
          $newZone['Area'] = intval(round(($zone['Area']*$newA)/$oldA));
          $newZone['MinAlarmPixels'] = intval(round(($newZone['MinAlarmPixels']*$newA)/$oldA));
          $newZone['MaxAlarmPixels'] = intval(round(($newZone['MaxAlarmPixels']*$newA)/$oldA));
          $newZone['MinFilterPixels'] = intval(round(($newZone['MinFilterPixels']*$newA)/$oldA));
          $newZone['MaxFilterPixels'] = intval(round(($newZone['MaxFilterPixels']*$newA)/$oldA));
          $newZone['MinBlobPixels'] = intval(round(($newZone['MinBlobPixels']*$newA)/$oldA));
          $newZone['MaxBlobPixels'] = intval(round(($newZone['MaxBlobPixels']*$newA)/$oldA));

          $changes = getFormChanges($zone, $newZone, $types);

          if ( count($changes) ) {
            dbQuery('UPDATE Zones SET '.implode(', ', $changes).' WHERE MonitorId=? AND Id=?',
              array($mid, $zone['Id']));
          }
        } // end foreach zone
      } // end if width and height
      $restart = true;
    } else if ( ! $user['MonitorIds'] ) {
      // Can only create new monitors if we are not restricted to specific monitors
# FIXME This is actually a race condition. Should lock the table.
      $maxSeq = dbFetchOne('SELECT MAX(Sequence) AS MaxSequence FROM Monitors', 'MaxSequence');
      $changes[] = 'Sequence = '.($maxSeq+1);

      $sql = 'INSERT INTO Monitors SET '.implode(', ', $changes);
      if ( dbQuery($sql) ) {
        $mid = dbInsertId();
        $zoneArea = $_REQUEST['newMonitor']['Width'] * $_REQUEST['newMonitor']['Height'];
        dbQuery("INSERT INTO Zones SET MonitorId = ?, Name = 'All', Type = 'Active', Units = 'Percent', NumCoords = 4, Coords = ?, Area=?, AlarmRGB = 0xff0000, CheckMethod = 'Blobs', MinPixelThreshold = 25, MinAlarmPixels=?, MaxAlarmPixels=?, FilterX = 3, FilterY = 3, MinFilterPixels=?, MaxFilterPixels=?, MinBlobPixels=?, MinBlobs = 1", array( $mid, sprintf( "%d,%d %d,%d %d,%d %d,%d", 0, 0, $_REQUEST['newMonitor']['Width']-1, 0, $_REQUEST['newMonitor']['Width']-1, $_REQUEST['newMonitor']['Height']-1, 0, $_REQUEST['newMonitor']['Height']-1 ), $zoneArea, intval(($zoneArea*3)/100), intval(($zoneArea*75)/100), intval(($zoneArea*3)/100), intval(($zoneArea*75)/100), intval(($zoneArea*2)/100)  ) );
        //$view = 'none';
        $Storage = new ZM\Storage($_REQUEST['newMonitor']['StorageId']);
        mkdir($Storage->Path().'/'.$mid, 0755);
        $saferName = basename($_REQUEST['newMonitor']['Name']);
        symlink($mid, $Storage->Path().'/'.$saferName);

      } else {
        ZM\Error('Error saving new Monitor.');
        $error_message = dbError($sql);
        return;
      }
    } else {
      ZM\Error('Users with Monitors restrictions cannot create new monitors.');
      return;
    }

    $restart = true;
  } else {
    ZM\Logger::Debug('No action due to no changes to Monitor');
  } # end if count(changes)

  if (
    ( !isset($_POST['newMonitor']['GroupIds']) )
    or
    ( count($_POST['newMonitor']['GroupIds']) != count($Monitor->GroupIds()) )
    or 
    array_diff($_POST['newMonitor']['GroupIds'], $Monitor->GroupIds())
  ) {
    if ( $Monitor->Id() )
      dbQuery('DELETE FROM Groups_Monitors WHERE MonitorId=?', array($mid));

    if ( isset($_POST['newMonitor']['GroupIds']) ) {
      foreach ( $_POST['newMonitor']['GroupIds'] as $group_id ) {
        dbQuery('INSERT INTO Groups_Monitors (GroupId,MonitorId) VALUES (?,?)', array($group_id, $mid));
      }
    }
  } // end if there has been a change of groups

  if ( ZM_OPT_X10 ) {
    $x10Changes = getFormChanges($x10Monitor, $_REQUEST['newX10Monitor']);

    if ( count($x10Changes) ) {
      if ( $x10Monitor && isset($_REQUEST['newX10Monitor']) ) {
        dbQuery('UPDATE TriggersX10 SET '.implode(', ', $x10Changes).' WHERE MonitorId=?', array($mid));
      } elseif ( !$user['MonitorIds'] ) {
        if ( !$x10Monitor ) {
          dbQuery('INSERT INTO TriggersX10 SET MonitorId = ?, '.implode(', ', $x10Changes), array($mid));
        } else {
          dbQuery('DELETE FROM TriggersX10 WHERE MonitorId = ?', array($mid));
        }
      }
      $restart = true;
    } # end if has x10Changes
  } # end if ZM_OPT_X10

  if ( $restart ) {
    
    $new_monitor = new ZM\Monitor($mid);

    if ( $new_monitor->Function() != 'None' and $new_monitor->Type() != 'WebSite' ) {
      $new_monitor->zmcControl('start');
      if ( ($new_monitor->Function() == 'Modect' or $new_monitor->Function == 'Mocord') and $new_monitor->Enabled() )
        $new_monitor->zmaControl('start');

      if ( $new_monitor->Controllable() ) {
        require_once('includes/control_functions.php');
        sendControlCommand($mid, 'quit');
      }
    }
    // really should thump zmwatch and maybe zmtrigger too.
    //daemonControl( 'restart', 'zmwatch.pl' );
    $refreshParent = true;
  } // end if restart
  $view = 'none';
} else {
  ZM\Warning("Unknown action $action in Monitor");
} // end if action == Delete
?>

//
// ZoneMinder Remote Camera Class Interface, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

#ifndef ZM_REMOTE_CAMERA_H
#define ZM_REMOTE_CAMERA_H

#include "zm_camera.h"
#include "zm_rtsp_auth.h"

#include <string>
#include <sys/types.h>
#include <sys/socket.h>
#include <netdb.h>
#include <arpa/inet.h>

//
// Class representing 'remote' cameras, i.e. those which are
// accessed over a network connection.
//
class RemoteCamera : public Camera
{
protected:
  std::string  protocol;
  std::string  host;
  std::string  port;
  std::string  path;
  std::string  auth;
  std::string  username;
  std::string  password;
  std::string  auth64;

  // Reworked authentication system
  // First try without authentication, even if we have a username and password
  // on receiving a 401 response, select authentication method (basic or digest)
  // fill required fields and set needAuth
  // subsequent requests can set the required authentication header.
  bool mNeedAuth;
  zm::Authenticator* mAuthenticator;
protected:
  struct addrinfo *hp;

public:
  RemoteCamera(
    unsigned int p_monitor_id,
    const std::string &p_proto,
    const std::string &p_host,
    const std::string &p_port,
    const std::string &p_path,
    int p_width,
    int p_height,
    int p_colours,
    int p_brightness,
    int p_contrast,
    int p_hue,
    int p_colour,
    bool p_capture,
    bool p_record_audio
  );
  virtual ~RemoteCamera();

  const std::string &Protocol() const { return( protocol ); }
  const std::string &Host() const { return( host ); }
  const std::string &Port() const { return( port ); }
  const std::string &Path() const { return( path ); }
  const std::string &Auth() const { return( auth ); }
  const std::string &Username() const { return( username ); }
  const std::string &Password() const { return( password ); }

  virtual void Initialise();
  virtual void Terminate() = 0;
  virtual int Connect() = 0;
  virtual int Disconnect() = 0;
  virtual int PreCapture() = 0;
  virtual int Capture( Image &image ) = 0;
  virtual int PostCapture() = 0;
  virtual int CaptureAndRecord( Image &image, bool recording, char* event_directory )=0;
};

#endif // ZM_REMOTE_CAMERA_H

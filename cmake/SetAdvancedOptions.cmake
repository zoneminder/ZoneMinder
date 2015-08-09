set(ZM_CONFIG_DIR "/${CMAKE_INSTALL_SYSCONFDIR}" CACHE PATH "Location of ZoneMinder configuration, default system config directory")
set(ZM_EXTRA_LIBS "" CACHE STRING "A list of optional libraries, separated by semicolons, e.g. ssl;theora")
set(ZM_MYSQL_ENGINE "InnoDB" CACHE STRING "MySQL engine to use with database, default: InnoDB")
set(ZM_NO_MMAP "OFF" CACHE BOOL "Set to ON to not use mmap shared memory. Shouldn't be enabled unless you experience problems with the shared memory. default: OFF")
set(ZM_NO_FFMPEG "OFF" CACHE BOOL "Set to ON to skip ffmpeg checks and force building ZM without ffmpeg. default: OFF")
set(ZM_NO_LIBVLC "OFF" CACHE BOOL "Set to ON to skip libvlc checks and force building ZM without libvlc. default: OFF")
set(ZM_NO_CURL "OFF" CACHE BOOL "Set to ON to skip cURL checks and force building ZM without cURL. default: OFF")
set(ZM_NO_X10 "OFF" CACHE BOOL "Set to ON to build ZoneMinder without X10 support. default: OFF")
set(ZM_ONVIF "OFF" CACHE BOOL "Set to ON to enable basic ONVIF support. This is EXPERIMENTAL and may not work with all cameras claiming to be ONVIF compliant. default: OFF")
set(ZM_PERL_MM_PARMS INSTALLDIRS=vendor NO_PACKLIST=1 NO_PERLLOCAL=1 CACHE STRING "By default, ZoneMinder's Perl modules are installed into the Vendor folders, as defined by your installation of Perl. You can change that here. Consult Perl's MakeMaker documentation for a definition of acceptable parameters. If you set this to something that causes the modules to be installed outside Perl's normal search path, then you will also need to set ZM_PERL_SEARCH_PATH accordingly.")
set(ZM_PERL_SEARCH_PATH "" CACHE PATH "Use to add a folder to your Perl's search path. This will need to be set in cases where ZM_PERL_MM_PARMS has been modified such that ZoneMinder's Perl modules are installed outside Perl's default search path.")
set(ZM_TARGET_DISTRO "" CACHE STRING "Build ZoneMinder for a specific distribution.  Currently, valid names are: f21, f20, el6, OS13")
mark_as_advanced(FORCE ZM_EXTRA_LIBS ZM_MYSQL_ENGINE ZM_NO_MMAP CMAKE_INSTALL_FULL_BINDIR ZM_PERL_MM_PARMS ZM_PERL_SEARCH_PATH ZM_TARGET_DISTRO ZM_CONFIG_DIR)

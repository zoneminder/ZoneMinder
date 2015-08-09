# pthread (using find_library and find_path)
find_library(PTHREAD_LIBRARIES pthread)
if(PTHREAD_LIBRARIES)
	set(HAVE_LIBPTHREAD 1)
	list(APPEND ZM_BIN_LIBS "${PTHREAD_LIBRARIES}")
	find_path(PTHREAD_INCLUDE_DIR pthread.h)
	if(PTHREAD_INCLUDE_DIR)
		include_directories("${PTHREAD_INCLUDE_DIR}")
		set(CMAKE_REQUIRED_INCLUDES "${PTHREAD_INCLUDE_DIR}")
	endif(PTHREAD_INCLUDE_DIR)
	mark_as_advanced(FORCE PTHREAD_LIBRARIES PTHREAD_INCLUDE_DIR)
	check_include_file("pthread.h" HAVE_PTHREAD_H)
	if(NOT HAVE_PTHREAD_H)
		message(FATAL_ERROR " zm requires pthread headers - check that pthread development packages are installed")
	endif(NOT HAVE_PTHREAD_H)
else(PTHREAD_LIBRARIES)
	message(FATAL_ERROR "zm requires pthread but it was not found on your system")
endif(PTHREAD_LIBRARIES)

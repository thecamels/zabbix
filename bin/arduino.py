#!/usr/bin/env python
import serial
import sys
import time

ser=serial.Serial('/dev/ttyACM0', 9600, timeout=0.5)
ser.open()
try:
	time.sleep(2)
	ser.flush()
	ser.write(sys.argv[1]+"\n")
	response=ser.readline()
	print response
#	ser.close()
except:
	print " ERROR "
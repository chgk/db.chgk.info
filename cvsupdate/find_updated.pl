#!/usr/bin/perl -w
use dbchgk;
while (<>) {
  if (m~Working file:.*[ /]([^/ ]+\.txt)~) {  
    $file = $1;  
  } 
  if ($file && /date: (\S+)/) {
    mydo("UPDATE Tournaments SET LastUpdated='$1' WHERE FileName='$file';\n");
    $file = '';  
  } 
}  
  

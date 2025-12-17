@echo off



For /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set dt=%%c-%%a-%%b)

For /f "tokens=1-4 delims=:." %%a in ('echo %time%') do (set tm=%%a%%b%%c%%d)

set bkupfilename=%1 %dt% %tm%.sql


"C:\Program Files\MySQL\MySQL Server 5.1\bin\mysqldump"  --routines -u "root" -p"oluwaseyi"  julitim> "julitim%bkupfilename%"






@echo off

SET SCRIPT_PATH=%~dp0
SET PATH_VISUAL_BACKUP=%SCRIPT_PATH%_test\data
SET NAME_COMPONENT=webcode

echo .
echo Backup of the dokuwiki pages that serves as visual into the directory %PATH_VISUAL_BACKUP%
echo .
echo .
SET DOKU_ROOT=%SCRIPT_PATH%..\..\..
SET DOKU_DATA=%DOKU_ROOT%\dokudata

SET PATH_VISUAL_PAGES=%DOKU_DATA%\pages\%NAME_COMPONENT%
SET PATH_VISUAL_PAGES_DST=%PATH_VISUAL_BACKUP%\pages

echo Copying the pages:
echo   * from %PATH_VISUAL_PAGES%
echo   * to %PATH_VISUAL_PAGES_DST%
echo .
xcopy /Y /E %PATH_VISUAL_PAGES% %PATH_VISUAL_PAGES_DST%
echo .

SET PATH_VISUAL_MEDIAS=%DOKU_DATA%\media\%NAME_COMPONENT%
SET PATH_VISUAL_MEDIAS_DST=%PATH_VISUAL_BACKUP%\media

echo Copying the images:
echo   * from %PATH_VISUAL_MEDIAS%
echo   * to %PATH_VISUAL_MEDIAS_DST%
echo .
xcopy /Y /E %PATH_VISUAL_MEDIAS% %PATH_VISUAL_MEDIAS_DST%
echo .
echo Done
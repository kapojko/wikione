cd upload
7z u ../upload.zip *
cd ..
7z u wikione-%1%.zip -x!.svn -x!wikione-????????.zip *

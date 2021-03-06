rm -rf ./mod/pumukit install/pumukit.zip
cp -r ./mod/pmkpersonalvideos/ ./mod/pumukit
mv ./mod/pumukit/backup/moodle2/restore_pmkpersonalvideos_activity_task.class.php ./mod/pumukit/backup/moodle2/restore_pumukit_activity_task.class.php
mv ./mod/pumukit/backup/moodle2/restore_pmkpersonalvideos_stepslib.php ./mod/pumukit/backup/moodle2/restore_pumukit_stepslib.php
mv ./mod/pumukit/backup/moodle2/backup_pmkpersonalvideos_stepslib.php ./mod/pumukit/backup/moodle2/backup_pumukit_stepslib.php
mv ./mod/pumukit/backup/moodle2/backup_pmkpersonalvideos_activity_task.class.php ./mod/pumukit/backup/moodle2/backup_pumukit_activity_task.class.php
mv ./mod/pumukit/lang/es/pmkpersonalvideos.php ./mod/pumukit/lang/es/pumukit.php
mv ./mod/pumukit/lang/en/pmkpersonalvideos.php ./mod/pumukit/lang/en/pumukit.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/version.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/view.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/README.txt
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/lib.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/backup/moodle2/backup_pumukit_activity_task.class.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/backup/moodle2/restore_pumukit_activity_task.class.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/backup/moodle2/backup_pumukit_stepslib.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/backup/moodle2/restore_pumukit_stepslib.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/settings.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/locallib.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/index.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/mod_form.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/classes/event/course_module_instance_list_viewed.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/classes/event/course_module_viewed.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/lang/es/pumukit.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/lang/en/pumukit.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/db/access.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/db/install.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/db/upgrade.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/db/log.php
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/db/install.xml
sed -i "s/pmkpersonalvideos/pumukit/g" ./mod/pumukit/db/uninstall.php

cd mod
zip -r ../install/pumukit.zip pumukit

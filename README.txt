AppsHub backend patch: Destination Builder / Short Video API alignment

Files included:
- app/Http/Controllers/Api/V1/HubController.php

Purpose:
- If a short-video section has source_channel/source_category but source_type is still short_video_all, the hub API now honors the selected channel/category.
- The hub API source metadata is normalized so Flutter receives short_video_channel or short_video_category consistently.

Install from /var/www/appshub with backups before overwrite.

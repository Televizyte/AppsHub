AppHub Legal Document Editor Blade compilation fix
- Removes nested Blade interpolation used only to display placeholder examples.
- Uses HTML entities for literal {{placeholder}} examples.
- No controller, route, storage, or document behavior changes.

<?php

return [
    'menu' => 'Media',
    'kicker' => 'Assets',
    'title' => 'Media library',
    'description' => 'A central place for images reused across articles and future portal modules.',

    'usage' => '{0} unused|{1} :count use|[2,*] :count uses',

    'upload' => [
        'title' => 'Add images',
        'description' => 'Upload up to 10 JPG, PNG or WEBP files at once, max 5 MB each.',
        'folder' => 'Folder / collection',
        'choose' => 'Choose files',
        'submit' => 'Upload',
    ],

    'filters' => [
        'search' => 'Search filename, title, ALT or caption…',
        'all_folders' => 'All folders',
        'apply' => 'Filter',
        'clear' => 'Clear',
    ],

    'actions' => [
        'edit' => 'Edit',
        'save' => 'Save metadata',
        'delete' => 'Delete file',
    ],

    'empty' => [
        'title' => 'The library is empty',
        'description' => 'Upload the first images so they can be reused in articles.',
    ],

    'edit' => [
        'title' => 'Edit media',
        'back' => 'Library',
        'filename' => 'File',
        'dimensions' => 'Dimensions',
        'size' => 'Size',
        'type' => 'Type',
        'folder' => 'Folder',
        'usage' => 'Uses',
        'metadata' => 'Metadata and organization',
    ],

    'fields' => [
        'title' => 'Asset title',
        'alt' => 'ALT text',
        'alt_help' => 'Short image description for accessibility and SEO.',
        'caption' => 'Caption / description',
        'folder' => 'Folder / collection',
    ],

    'delete' => [
        'title' => 'Delete asset',
        'description' => 'The file will be physically deleted and cannot be recovered.',
        'in_use' => 'This image is used by :count article(s). Change those publications first.',
        'confirm' => 'Permanently delete this file?',
    ],

    'messages' => [
        'uploaded' => '{1} Uploaded :count image.|[2,*] Uploaded :count images.',
        'updated' => 'Image metadata saved.',
        'deleted' => 'Image deleted from the library.',
        'in_use' => 'The image cannot be deleted because it is used by an article.',
    ],

    'article' => [
        'open_library' => 'Library',
        'choose_library' => 'Choose from library',
        'or_upload' => 'or upload new',
        'picker_title' => 'Choose hero image',
        'close' => 'Close media library',
        'search' => 'Filter library images…',
        'no_media' => 'No images available.',
        'latest_limit' => 'The picker shows the 100 newest assets.',
        'manage_library' => 'Manage full library',
    ],
];

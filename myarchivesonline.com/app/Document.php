<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'user_id', 'folder_id', 'name', 'folio_number', 'folder', 'description', 'file', 'created_by', 'status',
    ];

    private static $mimeTypes = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'application/vnd.ms-word.document.macroEnabled.12',
        'application/vnd.ms-word.template.macroEnabled.12',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'application/vnd.ms-excel.sheet.macroEnabled.12',
        'application/vnd.ms-excel.template.macroEnabled.12',
        'application/vnd.ms-excel.addin.macroEnabled.12',
        'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.presentationml.template',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        'application/vnd.ms-powerpoint.template.macroEnabled.12',
        'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        'application/x-iwork-pages-sffpages',
        'application/pdf',
        'text/plain',
        'text/csv',
    ];

    public static function getMimeTypes(){
        return self::$mimeTypes;
    }

    public static function showMimeType($mimeType){
        $map = [
            'application/msword' => 'Microsoft Word (.doc)',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Microsoft Word (.docx)',
            'application/vnd.ms-excel' => 'Microsoft Excel (.xls)',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Microsoft Excel (.xlsx)',
            'application/vnd.ms-powerpoint' => 'Microsoft PowerPoint (.ppt)',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'Microsoft PowerPoint (.pptx)',
            'application/pdf' => 'PDF Document',
            'text/plain' => 'Text File',
            'text/csv' => 'CSV Spreadsheet',
            'application/x-iwork-pages-sffpages' => 'Apple Pages',
        ];
        return $map[$mimeType] ?? $mimeType;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

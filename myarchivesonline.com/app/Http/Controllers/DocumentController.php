<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Document;
use App\Folder;
use Auth;
use Carbon\Carbon;

class DocumentController extends Controller{
    private $mimeTypes;

    public function __construct(){
        $this->middleware('auth');
        $this->mimeTypes = Document::getMimeTypes();
    }

    public function index(){
        $folders = Folder::where([
            ['user_id', Auth::id()],
            ['active', 1],
        ])->get();

        $documents = Document::where([
            ['user_id', Auth::id()],
            ['status', 'Available'],
        ])->get();
        return view('user.documents', ['folders' => $folders, 'documents' => $documents]);
    }

    public function search(Request $request){
        $folders = Folder::where([
            ['user_id', Auth::id()],
            ['active', 1],
        ])->get();

        $documents = Document::where([
            ['user_id', Auth::id()],
            ['name', 'like', '%'. $request->get('name') .'%'],
            ['folio_number', 'like', '%'. $request->get('folioNumber') .'%'],
            ['description', 'like', '%'. $request->get('description') .'%'],
            ['status', 'Available'],
        ])->get();

        return view('user.documents', ['folders' => $folders, 'documents' => $documents]);
    }

    public function create(){
        $folders = Folder::where([
            ['user_id', Auth::id()],
            ['active', 1],
        ])->get();
        return view('user.create-document', ['folders' => $folders]);
    }

    public function store(Request $request){
        $mimeTypes = Document::getMimeTypes();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'folder' => ['nullable', 'integer', 'exists:folders,id'],
            'file' => ['required', 'mimetypes:'.implode(',',$mimeTypes), 'max:20480'],
        ]);

        $currentUser = Auth::user();
        $folderName = null;

        if($request->filled('folder')){
            $folder = Folder::where([
                ['id', $request->get('folder')],
                ['user_id', Auth::id()],
            ])->first();
            abort_if(!$folder, 403, 'Unauthorized folder');
            $folderName = $folder->name;
        }

        $userSegment = preg_replace('/[^A-Za-z0-9_\- ]/', '', $currentUser->name .' '. ($currentUser->surname ?? ''));
        $userSegment = trim(preg_replace('/\s+/', ' ', $userSegment));

        if($folderName){
            $path = "documents/".$userSegment."/".$folderName;
        } else{
            $path = "documents/".$userSegment;
        }

        $file = str_replace(' ', '_', Carbon::now()->toDateTimeString().' '.$request->get('name').'.'.$request->file->getClientOriginalExtension());

        $document = new Document([
            'user_id' => Auth::id(),
            'folder_id' => $request->get('folder') ? $request->get('folder') : null,
            'name' => $request->get('name'),
            'folio_number' => $request->get('folioNumber'),
            'folder' => $folderName,
            'description' => $request->get('description'),
            'file' => $file,
            'created_by' => $userSegment,
        ]);

        $request->file->move(public_path($path), $file);

        $document->save();

        return redirect()->route('documents')->with('success','Document created successfully.');
    }

    public function show($document){
        $document = Document::where([
            ['name', $document],
            ['user_id', Auth::id()],
        ])->first();
        abort_if(!$document, 404);
        return view('user.document', ['document' => $document]);
    }

    public function edit($document){
        $document = Document::where([
            ['id', $document],
            ['user_id', Auth::id()],
        ])->first();
        abort_if(!$document, 404);

        $folders = Folder::where([
            ['user_id', Auth::id()],
            ['active', 1],
        ])->get();

        return view('user.edit-document', ['document' => $document, 'folders' => $folders]);
    }

    public function update(Request $request){
        $request->validate([
            'documentID' => ['required', 'integer', 'exists:documents,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'previousFile' => ['required', 'string'],
            'folder' => ['nullable', 'integer', 'exists:folders,id'],
            'file' => ['nullable', 'mimetypes:'.implode(',',$this->mimeTypes), 'max:20480'],
        ]);

        $document = Document::where([
            ['id', $request->get('documentID')],
            ['user_id', Auth::id()],
        ])->first();
        abort_if(!$document, 404);

        $currentUser = Auth::user();
        $folderName = null;

        if($request->filled('folder')){
            $folder = Folder::where([
                ['id', $request->get('folder')],
                ['user_id', Auth::id()],
            ])->first();
            abort_if(!$folder, 403, 'Unauthorized folder');
            $folderName = $folder->name;
        }

        $userSegment = preg_replace('/[^A-Za-z0-9_\- ]/', '', $currentUser->name .' '. ($currentUser->surname ?? ''));
        $userSegment = trim(preg_replace('/\s+/', ' ', $userSegment));

        if($folderName){
            $path = "documents/".$userSegment."/".$folderName;
        } else{
            $path = "documents/".$userSegment;
        }

        $file = $request->get('previousFile');

        if($request->hasFile('file')){
            $file = str_replace(' ', '_', Carbon::now()->toDateTimeString().' '.$request->get('name').'.'.$request->file->getClientOriginalExtension());
            $request->file->move(public_path($path), $file);

            $directoryName = public_path()."/documents/".$userSegment."/trash/";
            if(!is_dir($directoryName)){
                mkdir($directoryName, 0755, true);
            }

            $oldLocation = public_path(). "/documents/".$userSegment."/". ($document->folder ? $document->folder."/" : "") . $request->get('previousFile');
            if(file_exists($oldLocation)){
                rename($oldLocation, $directoryName.$request->get('previousFile'));
            }
        }

        $document->user_id = Auth::id();
        $document->folder_id = $request->get('folder') ? $request->get('folder') : null;
        $document->name = $request->get('name');
        $document->folio_number = $request->get('folioNumber');
        $document->folder = $folderName;
        $document->description = $request->get('description');
        $document->file = $file;
        $document->created_by = $userSegment;

        $document->save();

        return redirect()->route('documents')->with('success','Document edited successfully.');
    }

    public function download($document){
        $document = Document::where([
            ['id', $document],
            ['user_id', Auth::id()],
            ['status', 'Available'],
        ])->first();
        abort_if(!$document, 404);

        if($document->folder == null){
            $pathToFile = public_path(). "/documents/".$document->created_by."/" .$document->file;
        } else{
            $pathToFile = public_path(). "/documents/".$document->created_by."/".$document->folder."/" .$document->file;
        }

        abort_if(!file_exists($pathToFile), 404, 'Document file not found on disk');

        $detectedMime = mime_content_type($pathToFile) ?: 'application/octet-stream';
        $headers = [
            'Content-Type' => $detectedMime,
        ];

        return response()->download($pathToFile, $document->file, $headers);
    }

    public function destroy($document){
        $document = Document::where([
            ['id', $document],
            ['user_id', Auth::id()],
            ['status', 'Available'],
        ])->first();
        abort_if(!$document, 404);

        if($document->folder == null){
            $pathToFile = public_path(). "/documents/".$document->created_by."/" .$document->file;
        } else{
            $pathToFile = public_path(). "/documents/".$document->created_by."/".$document->folder."/" .$document->file;
        }

        $trashDir = public_path(). "/documents/".$document->created_by."/trash/";
        if(!is_dir($trashDir)){
            mkdir($trashDir, 0755, true);
        }
        if(file_exists($pathToFile)){
            rename($pathToFile, $trashDir . basename($pathToFile));
        }

        $document->status = 'Deleted';
        $document->save();

        return redirect()->route('documents')->with('success','Document deleted successfully.');
    }
}

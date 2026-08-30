@extends('layouts.app-user')

@section('content')

    <section class="content">
        <div class="container-fluid">
            <div class="block-header">
                <div class="row">
                    <div class="col-lg-12 col-md-6 col-sm-7">
                        <h2>Edit Document</h2>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('index') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Documents</a></li>
                            <li class="breadcrumb-item active">Edit Document</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row clearfix">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="header">
                            <h2>Edit Document</h2>
                            <ul class="header-dropdown m-r--5">
                                <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more-vert"></i> </a>
                                    <ul class="dropdown-menu pull-right">
                                        <li><a href="javascript:void(0);" data-toggle="modal" data-target="#newFolderModal">New Folder</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                        <div class="body">
                            <form id="form_validation" method="POST" action="{{ route('updateDocument') }}" enctype="multipart/form-data">
                                @csrf

                                <input type="hidden" class="form-control" name="documentID" value="{{ $document->id }}" required/>

                                <select name="folder" class="form-control show-tick">
                                    <option value="">-- No Folder --</option>
                                    @foreach($folders as $folder)
                                        <option value="{{ $folder->id }}" {{ $document->folder_id == $folder->id ? 'selected' : '' }}>{{ $folder->name }}</option>
                                    @endforeach
                                </select>

                                <div class="form-group form-float">
                                    <div class="form-line">
                                        <input type="text" class="form-control" name="name" placeholder="Name" value="{{ $document->name }}" required />
                                    </div>

                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group form-float">
                                    <div class="form-line">
                                        <input type="text" class="form-control" name="folioNumber" placeholder="Folio Number" value="{{ $document->folio_number }}" />
                                    </div>

                                    @error('folioNumber')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group form-float">
                                    <div class="form-line">
                                        <textarea class="form-control" name="description" placeholder="Description" rows="5" required>{{ $document->description }}</textarea>
                                    </div>

                                    @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <input type="hidden" class="form-control" name="previousFile" value="{{ $document->file }}" required/>

                                <div>
                                <input type="file" class="form-control-file border" name="file" value="" />

                                    @error('file')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group form-float"><div class="form-line"></div></div>

                                <div class="form-group">
                                    <input type="checkbox" id="checkbox" name="checkbox" required checked/>
                                    <label for="checkbox">I have read and accept the terms</label>
                                </div>

                                <button class="btn btn-raised btn-primary waves-effect" type="submit">UPDATE DOCUMENT</button>
                                <a href="{{ route('documents') }}" class="btn btn-raised btn-secondary waves-effect">CANCEL</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- New Folder Modal -->
    <div class="modal fade" id="newFolderModal">
        <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
            <h4 class="modal-title">New Folder</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Modal body -->
            <div class="modal-body">
                <form id="editNewFolderForm" method="POST" action="{{ route('storeFolder') }}">
                    @csrf

                    <div class="form-group form-float">
                        <div class="form-line">
                            <input type="text" class="form-control" name="name" placeholder="Folder Name" value="" required />

                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="submit" form="editNewFolderForm" class="btn btn-raised btn-primary waves-effect mr-1">Create Folder</button>
                <button type="button" class="btn btn-raised btn-secondary waves-effect" data-dismiss="modal">Close</button>
            </div>

        </div>
        </div>
    </div>

@endsection

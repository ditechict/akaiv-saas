@extends('layouts.app-user')

@section('content')
<section class="content">   
    <div class="container-fluid">
        <div class="block-header">
            <div class="row">
                <div class="col-lg-12 col-md-6 col-sm-7">
                    <h2>My Documents</h2>
                    <ul class="breadcrumb">                        
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Documents</a></li>
                        <li class="breadcrumb-item active">My Documents</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="header">
                        <h2> MY DOCUMENTS </h2>
                        <!--ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more-vert"></i> </a>
                                <ul class="dropdown-menu pull-right">
                                    <li><a href="javascript:void(0);">Action</a></li>
                                    <li><a href="javascript:void(0);">Another action</a></li>
                                    <li><a href="javascript:void(0);">Something else here</a></li>
                                </ul>
                            </li>
                        </ul-->
                    </div>
                    <div class="body">
                        <div class="alert alert-info">
                            <strong>Please Note:</strong> You can search one criteria.
                        </div>

                        <form method="POST" action="{{ route('searchDocument') }}">
                            @csrf

                            <div class="row clearfix">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" name="name" class="form-control" placeholder="Name" value="" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" name="folioNumber" class="form-control" placeholder="Folio Number" value="" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" name="description" class="form-control" placeholder="Description" value="" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-group">
                                        <input type="submit" class="form-control btn btn-raised btn-primary waves-effect" value="Search" />
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover js-basic-example dataTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Folio Number</th>
                                        <th>Folder</th>
                                        <th>Description</th>
                                        <th>Created At</th>
                                        <th>Last Modified</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>Name</th>
                                        <th>Folio Number</th>
                                        <th>Folder</th>
                                        <th>Description</th>
                                        <th>Created At</th>
                                        <th>Last Modified</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    @foreach($documents as $document)
                                    <tr>
                                        <td>{{ $document->name }}</td>
                                        <td>{{ $document->folio_number }}</td>
                                        <td>{{ $document->folder }}</td>
                                        <td>{{ $document->description }}</td>
                                        <td>{{ $document->created_at }}</td>
                                        <td>{{ $document->updated_at }}</td>
                                        <td>
                                            <a href="{{ route('downloadDocument', $document->id) }}" title="Download"><i class="fas fa-file-download"></i></a>
                                            <a href="{{ route('editDocument', $document->id) }}" title="Edit"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('destroyDocument', $document->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this document?');" style="display:inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link p-0" style="color:#d9534f" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

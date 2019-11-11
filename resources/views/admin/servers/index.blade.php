@extends('layouts.app')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Мониторинг</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">Главная</a></li>
                        <li class="breadcrumb-item">Мониторинг</li>
                        <li class="breadcrumb-item active">Серверы</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Список серверов</h3>
                            <div class="card-tools">
                                <div class="btn-group">
                                    <a href="{{route('servers.create')}}"
                                       class="btn btn-sm btn-info" title="Создание нового сервера">
                                        <i class="fa fa-plus-square"></i></a>
                                    @if($servers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                        <a href="{{route('servers.index', ['view' => 'all'])}}"
                                           class="btn btn-sm btn-outline-success" title="Показать весь список">
                                            <i class="fa fa-list" aria-hidden="true"></i></a>
                                    @else
                                        <a href="{{route('servers.index')}}"
                                           class="btn btn-sm btn-success" title="Показать весь список">
                                            <i class="fa fa-list" aria-hidden="true"></i></a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Адрес</th>
                                    <th>Описание</th>
                                    <th>Статус</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($servers as $server)
                                    @php
                                        /** @var \App\Http\Controllers\Admin\Servers\ServersController $server */
                                    @endphp

                                    <tr>
                                        @if($servers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                            <td>{{ ($servers->currentPage() - 1) * $servers->perPage() + $loop->iteration }}</td>
                                        @else
                                            <td>{{ $loop->iteration }}</td>
                                        @endif
                                        <td>{{ long2ip($server->addr) }}</td>
                                        <td>{{ $server->description }}</td>
                                        <td>
                                            @if($server->enabled == 1)
                                                <i class="fa fa-check-circle text-success"></i>
                                            @else
                                                <i class="fa fa-exclamation-circle text-danger"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{route('servers.show',$server->id)}}"
                                                   class="btn btn-xs bg-gradient-info" title="Просмотр сервера">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($servers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <ul class="pagination pagination-xs">
                            @if ($servers->lastPage() >= $servers->currentPage() && $servers->lastPage() > 1)
                                {{ $servers->links('vendor.pagination.bootstrap-4') }}
                            @endif
                        </ul>
                    @endif


                </div>
            </div>
        </div>
    </div>
@endsection

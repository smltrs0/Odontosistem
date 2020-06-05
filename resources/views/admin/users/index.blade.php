@extends('layouts.app')

@section('content')
    <div class="app-main__outer">
        <div class="app-main__inner">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">{{__('Users')}}</div>

                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">{{__('Name')}}</th>
                                        <th scope="col">{{__('Email')}}</th>
                                        <th scope="col">{{__('Roles')}}</th>
                                        <th scope="col">{{__('Actions')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                    <tr>
                                        <th scope="row">{{$user->id}}</th>
                                        <td>{{$user->name}}</td>
                                        <td>{{$user->email}}</td>
                                        <td>{{implode(',' ,$user->roles()->get()->pluck('name')->toArray())}}</td>
                                        <td>

                                        @can('edit-users') <!--can expects what gate you want to use-->
                                        <a href="{{route('admin.users.edit', $user->id)}}" ><button type="button" class="btn btn-warning float-left">{{__('Edit')}}</button></a>
                                        @endcan

                                        @can('delete-users')
                                        <form action="{{route('admin.users.destroy', $user)}}" method="POST" class="float-left">
                                            @csrf
                                            {{method_field('DELETE')}}
                                            <button type="submit" class="btn btn-danger">{{__('Delete')}}</button>
                                        </form>
                                        @endcan
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            {{ $users->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

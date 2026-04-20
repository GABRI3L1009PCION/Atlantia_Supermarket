@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Roles y permisos" subtitle="Matriz RBAC operativa del sistema." /><x-data-panel title="Roles" :items="$data" /></section>
@endsection


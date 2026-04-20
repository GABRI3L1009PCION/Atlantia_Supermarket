@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Moderacion de resenas" subtitle="Revision de resenas sospechosas o pendientes." /><x-data-panel title="Resenas" :items="$resenas" /></section>
@endsection


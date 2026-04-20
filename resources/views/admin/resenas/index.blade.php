@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Resenas" subtitle="Moderacion de opiniones de compradores." /><x-data-panel title="Resenas" :items="$resenas" /></section>
@endsection


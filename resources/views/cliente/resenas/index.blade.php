@extends('layouts.marketplace')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Mis resenas" subtitle="Opiniones enviadas sobre productos comprados." /><x-data-panel title="Resenas" :items="$resenas" /></section>
@endsection


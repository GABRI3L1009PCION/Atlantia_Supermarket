@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Monitor ML" subtitle="Salud, drift y versiones de modelos." /><x-data-panel title="Monitoreo" :items="$monitor" /></section>
@endsection


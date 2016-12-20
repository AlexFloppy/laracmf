@extends(isRole('admin')?'layouts.admin':config('app.layout'))

@section('title')
    Edit {{ $page->title }}
@stop

@section('top')
    <div class="page-header">
        <h1>Edit {{ $page->title }}</h1>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="pull-right">
                <a class="btn btn-success" href="{!! route('pages.show', array('pages' => $page->slug)) !!}"><i
                            class="fa fa-file-text"></i> Show Page</a> <a class="btn btn-danger" href="#delete_page"
                                                                          data-toggle="modal"
                                                                          data-target="#delete_page"><i
                            class="fa fa-times"></i> Delete Page</a>
            </div>
        </div>
    </div>
    <hr>
    <div class="box box-group">
        <div class="box-header">
            <h3 class="box-title">Edit page</h3>
        </div>
        <div class="box-body">
            <?php
            $form = ['url' => route('pages.update', ['pages' => $page->slug]),
                    '_method' => 'PATCH',
                    'method' => 'POST',
                    'button' => 'Save Page',
                    'defaults' => [
                            'title' => $page->title,
                            'nav_title' => $page->nav_title,
                            'slug' => $page->slug,
                            'icon' => $page->icon,
                            'body' => $page->body,
                            'css' => $page->css,
                            'categories' => $page->categories,
                            'js' => $page->js,
                            'show_title' => ($page->show_title == true),
                            'show_nav' => ($page->show_nav == true),
                    ],];
            ?>
            @include('pages.form')
        </div>
    </div>
@stop

@section('bottom')
    @if(isRole('editor'))
        @include('pages.delete')
    @endif
@stop

@section('css')
    <link rel="stylesheet" type="text/css"
          href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.2/css/bootstrap3/bootstrap-switch.min.css">
@stop

@section('js')
    <script type="text/javascript"
            src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.2/js/bootstrap-switch.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $(".make-switch").bootstrapSwitch();
        });
    </script>
@stop
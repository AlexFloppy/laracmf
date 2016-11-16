<form class="form-horizontal" action="{{ $form['url'] }}" method="{{ $form['method'] }}">

    {{ csrf_field() }}
    <input type="hidden" name="_method" value="{{ isset($form['_method'])? $form['_method'] : $form['method'] }}">

    <div class="form-group{!! ($errors->has('name')) ? ' has-error' : '' !!}">
        <label class="col-md-2 control-label" for="title">Environment Name</label>
        <div class="col-lg-8">
            <input name="name" id="name" value="{!! Request::old('name', $form['defaults']['name']) !!}" type="text" class="form-control" placeholder="Environment Name">
            {!! ($errors->has('name') ? $errors->first('name') : '') !!}
        </div>
    </div>
    <table class="env-table">
        <thead>
        <th>Key</th>
        <th>Value</th>
        </thead>
        <tbody>
        @foreach($form['defaults']['environment'] as $key => $value)
            <tr>
                <td>
                    <input name="keys[]" value="{{ $key }}" type="text" class="form-control">
                </td>
                <td>
                    <input name="values[]" value="{{ $value }}" type="text" class="form-control">
                </td>
                <td>
                    <button type="button" class="delete-pair"><i class="fa fa-close"></i></button>
                </td>
            </tr>
        @endforeach
        {!! ($errors->has('keys') ? $errors->first('keys') : '') !!}
        <br />
        {!! ($errors->has('values') ? $errors->first('values') : '') !!}
        </tbody>
    </table>
    <div class="form-group centering">
        <div class="col-lg-offset-4">
            <button class="btn btn-primary add-new-pair" type="button"><i class="fa fa-plus"></i> Add New Pair</button>
        </div>
        <br />
        <div class="col-lg-offset-4">
            <button class="btn btn-primary" type="submit"><i class="fa fa-rocket"></i> {!! $form['button'] !!}</button>
        </div>
    </div>
</form>

@if($errors->any())
    <div class="ortomax-alert ortomax-alert-danger" role="alert">
        <ul style="margin:0; padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

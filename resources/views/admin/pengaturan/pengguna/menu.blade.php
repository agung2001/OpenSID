<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Pengaturan Pengguna</h3>
        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body no-padding">
        <ul class="nav nav-pills nav-stacked">
            <li class='{{ jecho($tab_ini, 10, ' active') }}'><a href="{{ ci_route('man_user') }}"><i
                        class='fa fa-user'></i>Pengaturan Pengguna</a></li>
            <li class='{{ jecho($tab_ini, 11, ' active') }}'><a href="{{ ci_route('grup') }}"><i
                        class='fa fa-list'></i>Pengaturan Grup</a></li>
        </ul>
    </div>
</div>
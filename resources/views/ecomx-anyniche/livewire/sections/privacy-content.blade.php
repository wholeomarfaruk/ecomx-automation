<div style="display:contents">
    @if(count($toc))
        <nav class="jtc-legal__toc">
            <span class="jtc-legal__toc-label">On this page</span>
            <ul>
                @foreach($toc as $entry)
                    <li><a href="#{{ $entry['id'] }}">{{ $entry['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>
    @endif

    <div class="jtc-legal__body">
        {!! $body !!}
    </div>
</div>

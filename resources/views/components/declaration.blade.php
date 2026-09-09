<div {{ $attributes->merge(['class' => 'cc-declaration']) }}>
    @foreach($groups as $group)
        <section class="cc-declaration__group">
            <h3 class="cc-declaration__heading">{{ $group['label'] }}</h3>
            @if($group['description'])
                <p class="cc-declaration__description">{{ $group['description'] }}</p>
            @endif
            @if($group['scripts']->isEmpty())
                <p class="cc-declaration__empty">{{ $texts['declaration']['empty'] ?? '' }}</p>
            @else
                <div class="cc-declaration__table-wrap">
                    <table class="cc-declaration__table">
                        <thead>
                            <tr>
                                <th>{{ $texts['declaration']['name'] ?? 'Name' }}</th>
                                <th>{{ $texts['declaration']['provider'] ?? 'Provider' }}</th>
                                <th>{{ $texts['declaration']['purpose'] ?? 'Purpose' }}</th>
                                <th>{{ $texts['declaration']['expiry'] ?? 'Expiry' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group['scripts'] as $script)
                                @forelse($script->cookies as $cookie)
                                    <tr>
                                        <td>{{ $cookie->name }}</td>
                                        <td>{{ $cookie->provider ?? $script->provider ?? $script->name }}</td>
                                        <td>{{ $cookie->purpose }}</td>
                                        <td>{{ $cookie->expiry }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>{{ $script->name }}</td>
                                        <td>{{ $script->provider ?? '' }}</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforelse
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endforeach
    @if($showSettingsLink)
        <p class="cc-declaration__settings"><a href="#" data-cookieconsent="show">{{ $texts['settings_link'] ?? '' }}</a></p>
    @endif
</div>

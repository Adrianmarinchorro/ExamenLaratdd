<tr>
    <td rowspan="2">{{ $user->id }}</td>
    <th scope="row">
        {{ $user->name }} {{ $user->status }}
        @if($user->role != 'user')
            ({{ $user->role }})
        @endif
        <span class="status st-{{ $user->state }}"></span>
        <span class="note">{{ $user->team->name }}</span>
    </th>
    <td>{{ $user->email }}</td>
    <td>
        <span class="note">Registro: {{ $user->created_at->format('d/m/Y') }}</span>
        <span class="note">Último login: {{ $user->lastLogin ? $user->lastLogin->created_at->format('d/m/Y h:ia') : 'N/A'}}</span>
    </td>
    <td class="text-right">
        @if ($user->trashed())
            <form action="{{ route('user.destroy', $user) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-link"><span class="oi oi-circle-x"></span></button>
            </form>
        @else
            <form action="{{ route('users.trash', $user) }}" method="POST">
                @csrf
                @method('PATCH')
                <a href="{{ route('user.show', $user) }}" class="btn btn-outline-secondary btn-sm"><span class="oi oi-eye"></span></a>
                <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-secondary btn-sm"><span class="oi oi-pencil"></span></a>
                <button type="submit" class="btn btn-outline-danger btn-sm"><span class="oi oi-trash"></span></button>
            </form>
        @endif
    </td>
</tr>
<tr class="skills">
    <td colspan="1"><span class="note">{{ optional($user->profile->profession)->title }}</span></td>
    <td colspan="4"><span class="note">{{ $user->skills->implode('name', ',') ?: 'Sin habilidades' }}</span></td>
</tr>

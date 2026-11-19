<?php

namespace App\Support;

use Illuminate\Support\Facades\Redis;

final class ActiveCommunities
{
    public const STREAM = 'sebra:sphere:active';

    public static function update(string $operation, array $names = [], string $queryId = '', string $prefix = 'sebra:sphere'): ?string
    {
        // The query owner and the published selection change atomically.
        $script = <<<'LUA'
            local operation, names, query = ARGV[1], cjson.decode(ARGV[2]), ARGV[3]
            local raw = redis.call('GET', KEYS[1])
            local state = raw and cjson.decode(raw) or { names = {}, query = '' }

            if operation == 'started' then
                state.query = query
            elseif operation == 'set' then
                state = { names = names, query = '' }
            elseif state.query ~= query then
                return false
            elseif operation == 'answering' then
                state.names = names
            elseif operation == 'progress' then
                local seen = {}
                for _, name in ipairs(state.names) do seen[name] = true end
                for _, name in ipairs(names) do
                    if not seen[name] then
                        table.insert(state.names, name)
                        seen[name] = true
                    end
                end
            else
                state = { names = {}, query = '' }
            end

            redis.call('SET', KEYS[1], cjson.encode(state), 'PX', 15000)
            local payload = #state.names == 0 and '[]' or cjson.encode(state.names)
            return redis.call('XADD', KEYS[2], 'MAXLEN', 1, '*', 'active', payload)
            LUA;

        return Redis::connection()->client()->executeRaw([
            'EVAL', $script, '2', $prefix.':active-state', $prefix.':active',
            $operation, json_encode($names, JSON_THROW_ON_ERROR), $queryId,
        ]);
    }
}

import json
import sys

from graphify import serve
from graphify.__main__ import main


def publish(communities):
    if communities:
        print("SEBRA_PROGRESS " + json.dumps(communities), file=sys.stderr, flush=True)


def observe_bfs(traverse, graph, seeds, depth):
    original_neighbors = graph.neighbors
    seen = set()
    batch = []

    def record(node):
        data = graph.nodes[node]
        community = data.get("community", "unknown")
        if community in seen:
            return
        seen.add(community)
        batch.append({"id": community, "name": data.get("community_name") or f"Comunidad {community}"})

    for seed in seeds:
        record(seed)
    publish(batch)
    batch.clear()

    def neighbors(node):
        for neighbor in original_neighbors(node):
            record(neighbor)
            if len(batch) >= 8:
                publish(batch)
                batch.clear()
            yield neighbor

    # Graphify 0.9.109 uses neighbors only for traversal, then edges for rendering.
    graph.neighbors = neighbors
    try:
        result = traverse(graph, seeds, depth)
        nodes, edges = result
        ordered = list(dict.fromkeys([*seeds, *sorted(nodes, key=str)]))
        selected = set(ordered[:200])
        selected_edges = [[str(a), str(b)] for a, b in edges if a in selected and b in selected]
        print("SEBRA_SELECTION " + json.dumps({
            "node_ids": [str(node) for node in ordered[:200]],
            "seed_ids": [str(node) for node in seeds if node in selected],
            "edges": selected_edges[:400],
            "truncated": len(ordered) > 200 or len(selected_edges) > 400,
        }), file=sys.stderr, flush=True)
        return result
    finally:
        graph.neighbors = original_neighbors
        publish(batch)


original_bfs = serve._bfs
serve._bfs = lambda graph, seeds, depth: observe_bfs(original_bfs, graph, seeds, depth)

def structured_query(arguments, payload):
    if len(arguments) != 2 or arguments[0] != "--sebra-query":
        raise ValueError("Invalid structured query arguments")
    query = json.loads(payload)
    if not isinstance(query, dict) or query.get("intent") not in {"query", "explore", "navigate"}:
        raise ValueError("Only read queries are accepted")
    terms = query.get("terms")
    if (query.get("ambiguous") is not False or not isinstance(terms, list)
        or not 1 <= len(terms) <= 8
        or any(not isinstance(term, str) or not term.strip() or len(term) > 120
               or term.startswith("-") or any(ord(char) < 32 for char in term) for term in terms)):
        raise ValueError("Invalid Graphify search terms")
    return ["query", " ".join(terms), "--graph", arguments[1]]


if __name__ == "__main__":
    if sys.argv[1:2] == ["--sebra-query"]:
        try:
            sys.argv[1:] = structured_query(sys.argv[1:], sys.stdin.read(16384))
        except (ValueError, TypeError) as error:
            print(str(error), file=sys.stderr)
            sys.exit(2)
    main()

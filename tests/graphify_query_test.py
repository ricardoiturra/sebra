"""Adapter contract tests; run with the pinned Graphify environment (0.9.109)."""
import importlib.util
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("sebra_adapter", ROOT / "scripts/graphify_query.py")
adapter = importlib.util.module_from_spec(spec)
spec.loader.exec_module(adapter)


class StructuredQueryTest(unittest.TestCase):
    def test_only_read_queries_and_bounded_terms_are_accepted(self):
        for payload in [
            {"intent": "modify", "terms": ["User"], "ambiguous": False},
            {"intent": "explore", "terms": ["--graph"], "ambiguous": False},
            {"intent": "query", "terms": "User", "ambiguous": False},
            {"intent": "navigate", "terms": ["User"], "ambiguous": True},
            {"intent": "query", "terms": ["User\n--help"], "ambiguous": False},
            {"intent": "query", "terms": ["User"] * 9, "ambiguous": False},
        ]:
            with self.subTest(payload=payload), self.assertRaises(ValueError):
                adapter.structured_query(["--sebra-query", "/safe/graph.json"], json.dumps(payload))

    def test_provider_cannot_override_graph_path_or_command(self):
        result = adapter.structured_query(["--sebra-query", "/safe/graph.json"], json.dumps({
            "intent": "explore", "terms": ["User"], "ambiguous": False,
            "path": "/other/graph.json", "command": "delete",
        }))
        self.assertEqual(["query", "User", "--graph", "/safe/graph.json"], result)

    def test_real_graphify_preserves_nodes_edges_and_progress(self):
        with tempfile.TemporaryDirectory(prefix="sebra-adapter-test-") as directory:
            graph = Path(directory) / "graph.json"
            graph.write_text(json.dumps({
                "directed": False, "multigraph": False, "graph": {},
                "nodes": [
                    {"id": "user", "label": "User", "community": 0, "community_name": "Accounts", "source_file": "app/User.php"},
                    {"id": "team", "label": "Team", "community": 1, "community_name": "Teams", "source_file": "app/Team.php"},
                ],
                "links": [{"source": "user", "target": "team", "relation": "belongs to"}],
            }))
            result = subprocess.run(
                [sys.executable, str(ROOT / "scripts/graphify_query.py"), "--sebra-query", str(graph)],
                input=json.dumps({"intent": "navigate", "terms": ["User", "Team"], "ambiguous": False}),
                capture_output=True, text=True, timeout=12,
                env={**os.environ, "GRAPHIFY_QUERY_LOG_DISABLE": "1"},
            )
            self.assertEqual(0, result.returncode, result.stderr)
            self.assertIn("NODE User", result.stdout)
            self.assertIn("NODE Team", result.stdout)
            self.assertIn("app/User.php", result.stdout)
            self.assertIn("--belongs to", result.stdout)
            self.assertIn("SEBRA_PROGRESS", result.stderr)
            selection = next(json.loads(line.removeprefix("SEBRA_SELECTION ")) for line in result.stderr.splitlines() if line.startswith("SEBRA_SELECTION "))
            self.assertEqual({"user", "team"}, set(selection["node_ids"]))
            self.assertTrue(selection["edges"])
            self.assertNotIn("SecretEntity", result.stdout)


if __name__ == "__main__":
    unittest.main()

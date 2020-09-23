<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */

require("BkTree.php");

function time_callable($callable) {
	$start_time = microtime(true);
	return [
		"value" => $callable(),
		"time" => microtime(true) - $start_time
	];
}

function tree_create() {
	$tree = new BkTree("bktree.sqlite");
	
	echo("Populating tree - ");
	$time = microtime(true);
	$handle = fopen("enable1.shuf.txt", "r"); $i = 0;
	while(($line = fgets($handle)) !== false) {
		// if($i > 10) exit();
		$line = trim($line);
		$tree->add($line);
		$i++;
	}
	echo("done in ".round((microtime(true) - $time) * 1000, 2)."ms\n");
	
	fclose($handle);
	return $tree;
}
function tree_save(BkTree $tree) {
	echo("Saving tree\n");
	$tree->close();
}
function tree_load() {
	return new BkTree("bktree.sqlite");
}

function test_search_linear() {
	$start_time = microtime(true);
	$handle = fopen("enable1.shuf.txt", "r");
	while(($line = fgets($handle)) !== false) {
		if(levenshtein("cakke", trim($line)) > 2) continue;
		echo("linear match: ".trim($line)."\n");
	}
	echo("done in ".round((microtime(true) - $start_time) * 1000, 2)."ms\n");
	exit();
}

function writegraph() {
	global $tree;
	
	echo("Writing graph to bktree.dot\n");
	$handle = fopen("bktree.dot", "w");
	fwrite($handle, "// Generated by bktreetest.php by Starbeamrainbowlabs\n");
	fwrite($handle, "// Date: ".date("r")."\n");
	fwrite($handle, "digraph BkTree {\n");
	fwrite($handle, "\tgraph [bgcolor=\"transparent\", nodesep=\"5\", root=\"N0\"];\n");
	fwrite($handle, "\tnode [shape=\"point\", color=\"#cb3d38\"];\n");
	fwrite($handle, "\tedge [arrowhead=\"open\", gradientangle=\"90\", style=\"filled\", color=\"#fcba2280\"];\n"); // #8cc03280
	foreach($tree->walk() as $next) {
		if($next->id == 0) continue; // Skip the first node
		fwrite($handle, "\tN$next->parent_id -> N$next->id;\n");
	}
	fwrite($handle, "}\n");
	fclose($handle);
}

if(file_exists("bktree.sqlite"))
	$tree = time_callable("tree_load");
else
	$tree = time_callable("tree_create");

echo("Tree created in ".($tree["time"]*1000)."ms\n");
$tree = $tree["value"];

// writegraph(); exit();

function test_auto() {
	global $tree;
	for($i = 0; $i < 1; $i++) {
		$start_time = microtime(true);
		$results = $tree->lookup("cakke", 2);
		echo("Lookup complete in ".round((microtime(true) - $start_time)*1000, 2)."ms (".count($results)." results found)\n");
	}
	exit();
}

// test_auto();

echo("BkTree Test CLI\n");
echo("Exit with .exit\n");
echo("This ensures the tree is saved to disk\n");

while(true) {
	$line = readline("> "); // Newline is removed automatically
	if(strlen($line) == 0) continue;
	
	readline_add_history($line);
	
	if($line[0] == ".") {
		$parts = explode(" ", $line, 2);
		switch ($parts[0]) {
			case ".quit":
			case ".exit":
				$result = time_callable(function() use ($tree) {
					tree_save($tree);
				});
				echo("Serialised tree in ".round($result["time"] * 1000, 2)."ms\n");
				exit("exit\n");
				break;
			
			case ".help":
				echo("dot commands:
.exit               Exit, saving edits to the tree to disk
.writegraph         Write a graphviz dot file to disk representing the tree in the current directory
.stats              Compute statistics about the tree
.trace {{string}}   Trace the path through the tree to {{string}}
.remove {{string}}  Delete {{string}} from the tree
.add {{string}}     Add {{string}} to the tree
");
				break;
			
			case ".writegraph":
				writegraph();
				break;
				
			case ".remove":
				$start_time = microtime(true);
				$result = $tree->remove($parts[1]);
				echo("{$parts[1]}".($result?"":" not")." successfully deleted from the tree in ".round((microtime(true) - $start_time)*1000)."ms\n");
				break;
			case ".add":
				$start_time = microtime(true);
				$depth = $tree->add($parts[1]);
				echo("{$parts[1]} successfully added to the tree at depth $depth in ".round((microtime(true) - $start_time)*1000)."ms\n");
				break;
			
			case ".trace":
				$trace = $tree->trace($parts[1]);
				foreach($trace as $depth => $item) {
					echo("$depth: {$item->node->value} (#$item->id)\n");
				}
				break;
			
			case ".stats":
				echo("Tree stats: ");
				var_dump($tree->stats());
				break;
			
			default:
				echo("Error: Unknown dot-command {$parts[0]} (try .help)\n");
				break;
		}
		continue;
	}
	
	// var_dump($line);
	
	$time = microtime(true);
	$results = $tree->lookup($line, 2); $i = 0;
	$time = round((microtime(true) - $time)*1000, 2);
	$time_sort = microtime(true);
	// Note that adding a cache here doesn't make a significant different to performance
	// The overhead of calling a function far outweighs that of calling levenshtein(), apparently
	usort($results, function($a, $b) use ($line, $tree) {
		return $tree->edit_distance($a, $line) - $tree->edit_distance($b, $line);
	});
	$time_sort = round((microtime(true) - $time_sort)*1000, 2);
	foreach($results as $result) {
		echo(
			str_pad($i, 5, " ", STR_PAD_LEFT).": ".
			str_pad($result, 20).
			" dist ".$tree->edit_distance($result, $line).
			"\n"
		);
		$i++;
	}
	// $start_time_inc = microtime(true);
	// $i = 0;
	// foreach($tree->lookup($line, 2) as $result) {
	// 	// var_dump($result);
	// 	echo(
	// 		str_pad(
	// 			str_pad("$i: $result", 20)."dist ".levenshtein($result, $line),
	// 			40
	// 		).
	// 		"+".round((microtime(true) - $start_time_inc)*1000, 2)."ms\n"
	// 	);
	// 	// readline("(press enter to continue)");
	// 
	// 	$start_time_inc = microtime(true);
	// 	$i++;
	// }
	echo("Found $i results in {$time}ms (+{$time_sort}ms sort)\n");
}

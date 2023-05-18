<?php

class ScoreHeap extends \SplHeap
{
    protected function compare($value1, $value2): int
    {
        if ($value1->totalScore === $value2->totalScore) {
            return 0;
        }
        return ($value1->totalScore < $value2->totalScore) ? 1 : -1;
    }
}

class Manhattan
{
    public function compare(Node $node, Node $goal): float|int
    {
        $deltaX = abs($node->x - $goal->x);
        $deltaY = abs($node->y - $goal->y);
        return $deltaX + $deltaY;
    }
}

class Astar
{
    public function __construct(
        private readonly Grid $nodes,
        private bool          $diagonal = false,
        private array         $blocked = [],
        private               $heuristic = new Manhattan(),
    ) {

    }

    public function blocked(array $types): void
    {
        $this->blocked = $types;
    }

    public function enableDiagonal(): void
    {
        $this->diagonal = true;
    }

    public function search(Node $start, Node $end): array
    {
        $heap = new ScoreHeap();
        $heap->insert($start);

        $current = $this->fillHeap($heap, $start, $end);
        if ($current !== $end) {
            return [];
        }

        return $this->getReversedPath($current);

    }

    private function fillHeap(\SplHeap $heap, Node $current, Node $end): Node
    {
        while ($heap->valid() && $current !== $end) {
            /** @var Node $current */
            $current = $heap->extract();

            $current->close();
            $neighbors = $this->nodes->getNeighbors($current, $this->diagonal);
            foreach ($neighbors as $neighbor) {
                if ($neighbor->isClosed() || in_array($neighbor->costs, $this->blocked)) {
                    continue;
                }
                $score = $current->score + $neighbor->costs;
                $visited = $neighbor->isVisited();
                if (!$visited || $score < $neighbor->score) {
                    $neighbor->visit();
                    $neighbor->parent = $current;
                    $neighbor->guessedScore = $this->heuristic->compare($neighbor, $end);
                    $neighbor->score = $score;
                    $neighbor->totalScore = $neighbor->score + $neighbor->guessedScore;
                    if (!$visited) {
                        $heap->insert($neighbor);
                    }
                }
            }
        }

        return $current;
    }

    private function getReversedPath(Node $current): array
    {
        $result = [];
        while ($current->parent) {
            $result[] = $current;
            $current = $current->parent;
        }
        $result[]=$current;
        return array_reverse($result);
    }
}

class Grid
{
    public function __construct(array $grid, private array $nodes = [])
    {
        foreach ($grid as $y => $cols) {
            foreach ($cols as $x => $value) {
                $this->nodes[$y][$x] = new Node($x, $y, $value);
            }
        }
    }

    public function getPoint($y, $x)
    {
        return $this->nodes[$y][$x] ?? false;
    }

    /**
     * @param Node $node
     * @param bool $diagonal
     * @return Node[]
     */
    public function getNeighbors(Node $node, bool $diagonal = false): array
    {
        $result = [];
        $x = $node->x;
        $y = $node->y;

        $neighbourLocations = [
            [$y - 1, $x],
            [$y + 1, $x],
            [$y, $x - 1],
            [$y, $x + 1]
        ];
        if ($diagonal) {
            $neighbourLocations[] = [$y - 1, $x - 1];
            $neighbourLocations[] = [$y + 1, $x - 1];
            $neighbourLocations[] = [$y - 1, $x + 1];
            $neighbourLocations[] = [$y + 1, $x + 1];
        }

        foreach ($neighbourLocations as $location) {
            list($y, $x) = $location;
            $node = $this->getPoint($y, $x);
            if ($node) {
                $result[] = $node;
            }

        }
        return $result;
    }
}

class Node
{
    public function __construct(
        public int $x = 0,
        public int $y = 0,
        public int $costs = 0,
        public bool $visited = false,
        public bool $closed = false,
        public $parent = null,
        public int $totalScore = 0,
        public int $guessedScore = 0,
        public int $score = 0
    )
    {
    }

    public function visit(): void
    {
        $this->visited = true;
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function isVisited(): bool
    {
        return $this->visited;
    }
}
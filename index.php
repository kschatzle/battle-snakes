<?php
require_once 'Game.php';

$uri = strtok($_SERVER['REQUEST_URI'], '?');

function in()
{
    return json_decode(
        json: file_get_contents('php://input'),
        associative: false,
        flags: JSON_THROW_ON_ERROR
    );
}

enum Move
{
    case up;
    case down;
    case left;
    case right;
}

function start()
{
    $data = in();
    return $data;
}

function move()
{
    $data = in();
    $board = $data?->board;
    $snakes = $board?->snakes;
    $you = $data?->you;

//    $board->height;
//    $board->width;
//    $map = array_fill(0, 10, array_fill(0, 10, 0));
    $map = [
        [0, 0, 0, 0, 0],
        [0, 0, 0, 0, 0],
        [0, 0, 0, 0, 0],
        [0, 0, 0, 0, 0],
        [0, 0, 0, 0, 1],
    ];
    $map = array_fill(0, $board->height, array_fill(0, $board->width, 0));

    foreach ($you->body as $bod) {
        $map[$bod->x][$bod->y] = 1;
    }

    foreach ($snakes as $snake) {
        foreach ($snake->body as $bod) {
            $map[$bod->x][$bod->y] = 1;
        }
    }

    $map = new Grid($map);
    $astar = new Astar(nodes: $map, blocked: [1]);

    $start = $map->getPoint($you?->head?->y ?? 0, $you?->head->x ?? 0);
    $count = -1;
    $winner = null;
    foreach ($board->food as $food) {
        $end  = $map->getPoint($food->y, $food->x);
        $curr = $astar->search($start, $end);
        if (count($curr) > $count) {
            $winner = $curr;
            $count = count($curr);
        }
    }
    $winner = $winner[1];

    $response = new stdClass();
    $move = match(true) {
        ($you->head->x < $winner->x) => Move::right->name,
        ($you->head->x > $winner->x) => Move::left->name,
        ($you->head->y < $winner->y) => Move::down->name,
        ($you->head->y > $winner->y) => Move::up->name,
    };

    $response->move = $move;

    return $response;
}

function stop()
{
    return '';
}

function ping()
{
    return 'pong';
}

try {
    $data = match (trim($uri, '/')) {
        'start' => start(),
        'move' => move(),
        'end', '' => stop(),
        'ping' => ping(),
        default => '404 not found'
    };
} catch (\Throwable $t) {
    $data = new stdClass();
    $data->error = $t->getMessage();
} finally {
    header('Content-Type: application/json');
    echo json_encode($data);
}


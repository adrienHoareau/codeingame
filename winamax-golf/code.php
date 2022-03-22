<?php

/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 * */

fscanf(STDIN, "%d %d", $width, $height);
$rows = [];
for ($i = 0; $i < $height; $i++) {
    fscanf(STDIN, "%s", $row);
    $rows[] = $row;
}

//error_log(var_export($rows, true));

function echoLN($str)
{
    //echo $str . "\n";
}

class Point
{

    public $x;
    public $y;

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function __toString()
    {
        return $this->x . '-' . $this->y;
    }

}

class Grid
{

    public $width;
    public $height;
    private $tiles = [];
    private $ballTiles = [];
    private $holeTiles = [];

    public function __construct($width, $height, $rows)
    {
        $this->width = $width;
        $this->height = $height;
        $this->draw($rows);
    }

    private function draw($rows)
    {
        $y = 0;
        foreach ($rows as $row) {
            $x = 0;
            foreach (str_split($row) as $type) {
                $pt = new Point($x, $y);
                $tile = new Tile($pt, $type);
                $this->tiles[$x][$y] = $tile;
                if ($tile->hasBall()) {
                    $this->ballTiles[] = $tile;
                }
                if ($tile->isHole()) {
                    $this->holeTiles[] = $tile;
                }
                ++$x;
            }
            ++$y;
        }
    }

    public function render()
    {
        $str = "";
        for ($h = 0; $h < $this->height; $h++) {
            for ($w = 0; $w < $this->width; $w++) {
                $tile = $this->tiles[$w][$h];
                $str .= $tile->getStringableType();
            }
            $str .= "\n";
        }
        return $str;
    }

    public function replaceTiles(int $ballId, array $newTiles)
    {
        //error_log(var_export($newTiles, true));die;
        $firstTile = current($newTiles);
        $this->setBallDirection($ballId, $firstTile);
        foreach ($newTiles as $newTile) {
            $tile = $this->tiles[$newTile->x][$newTile->y];
            if ($tile->isHole()) {
                $newTile->toGrass();
            }
            $this->tiles[$newTile->x][$newTile->y] = $newTile;
        }
    }

    private function setBallDirection(int $ballId, Tile $tile)
    {
        $ball = $this->ballTiles[$ballId];
        $ball->setType($tile->getType());
    }

    public function getTiles()
    {
        return $this->tiles;
    }

    public function getBallTiles()
    {
        return $this->ballTiles;
    }

    public function getHoleTiles()
    {
        return $this->holeTiles;
    }

    public function isHole(Point $pt): bool
    {
        $tile = $this->tiles[$pt->x][$pt->y];

        return $tile->isHole();
    }

    public function getTile(Point $pt)
    {
        $offset = $this->tiles[$pt->x] ?? null;
        if (null === $offset) {
            throw new \Exception($pt->x . ' --offset-- ' . $pt->y);
        }
        $tile = $offset[$pt->y] ?? null;
        if (null === $tile) {
            throw new \Exception($pt->x . ' --tile-- ' . $pt->y);
        }
        return $tile;
    }

}

class Tile
{

    private $point;
    private $type;
    private $hasBall = false;
    private $nbBallHits = 0;
    public $x;
    public $y;

    public function __construct(Point $point, $type)
    {
        $this->point = $point;
        $this->x = $this->point->x;
        $this->y = $this->point->y;
        $this->type = $this->removeBall($type);
        $this->hasBall = is_numeric($type);
        if ($this->hasBall) {
            $this->nbBallHits = (int) $type;
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function getStringableType()
    {
        if ($this->isHole() || $this->isWater()) {
            return '.';
        }

        return $this->type;
    }

    private function removeBall($type)
    {
        return is_numeric($type) ? '.' : $type;
    }

    public function getPoint()
    {
        return $this->point;
    }

    public function hasBall()
    {
        return $this->hasBall;
    }

    public function isGrass()
    {
        return $this->type === '.';
    }

    public function isWater()
    {
        return $this->type === 'X';
    }

    public function isHole()
    {
        return $this->type === 'H';
    }

    public function toHole()
    {
        return $this->type = 'H';
    }

    public function getNbBallHits()
    {
        return $this->nbBallHits;
    }

    public function toGrass()
    {
        $this->type = '.';
    }

    public function isLeft()
    {
        return $this->type === '<';
    }

    public function isRight()
    {
        return $this->type === '>';
    }

    public function isUp()
    {
        return $this->type === 'v';
    }

    public function isDown()
    {
        return $this->type === '^';
    }

    public function setType(string $type)
    {
        return $this->type = $type;
    }

    public function getStringPoint(): string
    {
        return (string) $this->point;
    }

    public function __toString()
    {
        return (string) $this->point . ' : ' . $this->type;
    }

}

class BallToHoleSelector
{

    private $holesToFit = [];
    private $balls = [];
    private $ballsCounts = [];

    public function getUniqueBallToAllPaths(array $allPaths): array
    {
        //get a simple array with ballId => coordonates
        foreach ($allPaths as $ballId => $paths) {
            foreach ($paths as $coordonates) {
                $coordonate = array_key_last($coordonates);
                $this->holesToFit[$coordonate] = null;
                $this->balls[$ballId][] = $coordonate;
            }
        }
        foreach ($this->balls as $ballId => $coordonates) {
            $this->ballsCounts[$ballId] = ['nb' => count($coordonates), 'counter' => 0];
        }
        
        //print_r($this->holesToFit);
        //print_r($this->balls[7]);
        //print_r($this->balls[8]);die;
        foreach ($this->balls as $ballId => $unused) {
            $this->recursiveFitHole($ballId);
        }

        return $this->holesToFit;
    }

    private function recursiveFitHole(int $ballId, ?string $skipCoordonate = null)
    {
        //echoLN("fit hole with ball : $ballId");
        if (!isset($this->balls[$ballId])) {
            return;
        }
        foreach ($this->balls[$ballId] as $coordonate) {
            if ($coordonate === $skipCoordonate) {
                continue;
            }
            if (null === $this->holesToFit[$coordonate]) {
                //echoLN("fill hole $coordonate with ball : $ballId");
                $this->holesToFit[$coordonate] = $ballId;
                return;
            }
        }
        //echoLN("all holes filled, ballId: $ballId");
        //print_r($this->balls[$ballId]);
        $offset = ++$this->ballsCounts[$ballId]['counter'];
        if ($offset > $this->ballsCounts[$ballId]['nb'] - 1) {
            //reset counter
            $this->ballsCounts[$ballId]['counter'] = 0;
            $offset = 0;
        }
        $coordonate = $this->balls[$ballId][$offset];
        $fittedBallId = $this->holesToFit[$coordonate];
        $this->holesToFit[$coordonate] = $ballId;
        $this->recursiveFitHole($fittedBallId, $coordonate);
    }

}

/*
  class CrossCoordonates
  {
  private $coordonates = [];

  public function addCoordonates(array $coordonates, int $ballId, int $pathId)
  {
  foreach ($coordonates as $coordonate) {
  $this->coordonates[$coordonate][] = ['ballId' => $ballId, 'pathId' => $pathId];
  }
  }

  public function getCoordonatesOnlyUsedOnce()
  {
  $coordonates = [];

  }
  } */

class PathFinder
{

    private $grid;
    private $pathsDrawer;

    public function __construct(Grid $grid, PathsDrawer $pathDrawer)
    {
        $this->grid = $grid;
        $this->pathsDrawer = $pathDrawer;
    }

    public function drawValidPaths()
    {
        $allPaths = [];
        foreach ($this->grid->getBallTiles() as $ballId => $ball) {
            $allPaths[$ballId] = array_merge($allPaths[$ballId] ?? [], $this->pathsDrawer->drawAllPaths($ball));
        }
        //print_r($allPaths);die();
        $ballToHoleSelector = new BallToHoleSelector();
        $holesFilled = $ballToHoleSelector->getUniqueBallToAllPaths($allPaths);
        //print_r($holesFilled);die();
        $validPaths = [];
        foreach ($holesFilled as $holeCoordonate => $ballId) {
            foreach ($allPaths[$ballId] as $path) {
                if ($holeCoordonate === array_key_last($path)) {
                    $validPaths[$ballId] = $path;
                }
            }
        }
        //print_r($validPaths);die();

        return $this->createGridFromPaths($validPaths);
    }

    private function createGridFromPaths(array $validPaths)
    {
        $grid = clone $this->grid;
        foreach ($validPaths as $ballId => $paths) {
            $grid->replaceTiles($ballId, $paths);
        }

        return $grid;
    }

}

class PathsDrawer
{

    private $grid;

    public function __construct(Grid $grid)
    {
        $this->grid = $grid;
    }

    public function drawAllPaths(Tile $ball): array
    {
        //echoLN("drawing paths for ball {$ball->x},{$ball->y}");
        $nbHits = $ball->getNbBallHits();
        $paths = new Paths();
        $this->drawRecursiveSegment($ball->getPoint(), $paths, $paths->id, $nbHits);

        $completedPaths = $paths->getCompletedPaths();
        unset($paths);
        
        return $completedPaths;
    }

    private function drawRecursiveSegment(Point $pt, Paths $paths, int $pathId, int $nbHits): Paths
    {
        //$tile = clone $fromTile;
        //echoLN("handling pt {$pt->x},{$pt->y}");
        $gridTile = $this->grid->getTile($pt);
        if ($gridTile->isHole()) {
            //echoLN("hole found {$pt->x},{$pt->y} (#$pathId)");
            $tile = new Tile($pt, 'H');
            $paths->addTile($tile, $pathId);
            $paths->setCompleted($pathId);
            return $paths;
        }
        if ($nbHits === 0) {
            //echoLN("no more hit");
            return $paths;
        }
        if ($this->canMoveRight($pt, $nbHits, $paths, $pathId)) {
            //echoLN("moving right x$nbHits (#$pathId)");
            $newPathId = $paths->clonePath($pathId);
            $tile = new Tile($pt, '>');
            $paths->addTile($tile, $newPathId);
            $newPt = $this->addXTilesToTheRight($pt, $paths, $newPathId, $nbHits);
            $this->drawRecursiveSegment($newPt, $paths, $newPathId, $nbHits - 1);
        }
        if ($this->canMoveLeft($pt, $nbHits, $paths, $pathId)) {
            //echoLN("moving left x$nbHits (#$pathId)");
            $newPathId = $paths->clonePath($pathId);
            $tile = new Tile($pt, '<');
            $paths->addTile($tile, $newPathId);
            $newPt = $this->addXTilesToTheLeft($pt, $paths, $newPathId, $nbHits);
            $this->drawRecursiveSegment($newPt, $paths, $newPathId, $nbHits - 1);
        }
        if ($this->canMoveUp($pt, $nbHits, $paths, $pathId)) {
            //echoLN("moving Up x$nbHits (#$pathId)");
            $newPathId = $paths->clonePath($pathId);
            $tile = new Tile($pt, 'v');
            $paths->addTile($tile, $newPathId);
            $newPt = $this->addXTilesToTheTop($pt, $paths, $newPathId, $nbHits);
            $this->drawRecursiveSegment($newPt, $paths, $newPathId, $nbHits - 1);
        }
        if ($this->canMoveDown($pt, $nbHits, $paths, $pathId)) {
            //echoLN("moving down x$nbHits  (#$pathId)");
            $newPathId = $paths->clonePath($pathId);
            $tile = new Tile($pt, '^');
            $paths->addTile($tile, $newPathId);
            $newPt = $this->addXTilesToTheBottom($pt, $paths, $newPathId, $nbHits);
            $this->drawRecursiveSegment($newPt, $paths, $newPathId, $nbHits - 1);
        }
        //echoLN("can't move x$nbHits (#$pathId)");

        return $paths;
    }

    private function canMoveRight(Point $pt, int $nbHits, Paths $paths, int $pathId): bool
    {
        if ($pt->x + $nbHits > $this->grid->width - 1) {
            return false;
        }
        $destPt = new Point($pt->x + $nbHits, $pt->y);

        return $this->isDestinationReachable($destPt, $paths, $pathId);
    }

    private function canMoveLeft(Point $pt, int $nbHits, Paths $paths, int $pathId): bool
    {
        if ($pt->x - $nbHits < 0) {
            return false;
        }
        $destPt = new Point($pt->x - $nbHits, $pt->y);

        return $this->isDestinationReachable($destPt, $paths, $pathId);
    }

    private function canMoveUp(Point $pt, int $nbHits, Paths $paths, int $pathId): bool
    {
        if ($pt->y + $nbHits > $this->grid->height - 1) {
            return false;
        }
        $destPt = new Point($pt->x, $pt->y + $nbHits);

        return $this->isDestinationReachable($destPt, $paths, $pathId);
    }

    private function canMoveDown(Point $pt, int $nbHits, Paths $paths, int $pathId): bool
    {
        if ($pt->y - $nbHits < 0) {
            return false;
        }
        $destPt = new Point($pt->x, $pt->y - $nbHits);

        return $this->isDestinationReachable($destPt, $paths, $pathId);
    }

    private function isDestinationReachable(Point $pt, Paths $paths, int $pathId): bool
    {
        if ($paths->hasPoint($pt, $pathId)) {
            return false;
        }
        $tile = $this->grid->getTile($pt);
        if ($tile->isWater() || $tile->hasBall()) {
            return false;
        }

        return true;
    }

    private function addXTilesToTheRight(Point $pt, Paths $paths, int $pathId, int $nbHits): Point
    {
        $x = $pt->x;
        $y = $pt->y;
        while ($nbHits - 1) {
            $newPt = new Point(++$x, $y);
            $tile = new Tile($newPt, '>');
            $paths->addTile($tile, $pathId);
            --$nbHits;
        }

        return new Point(++$x, $y);
    }

    private function addXTilesToTheleft(Point $pt, Paths $paths, int $pathId, int $nbHits): Point
    {
        $x = $pt->x;
        $y = $pt->y;
        while ($nbHits - 1) {
            $newPt = new Point(--$x, $y);
            $tile = new Tile($newPt, '<');
            $paths->addTile($tile, $pathId);
            --$nbHits;
        }

        return new Point(--$x, $y);
    }

    private function addXTilesToTheTop(Point $pt, Paths $paths, int $pathId, int $nbHits): Point
    {
        $x = $pt->x;
        $y = $pt->y;
        while ($nbHits - 1) {
            $newPt = new Point($x, ++$y);
            $tile = new Tile($newPt, 'v');
            $paths->addTile($tile, $pathId);
            --$nbHits;
        }

        return new Point($x, ++$y);
    }

    private function addXTilesToTheBottom(Point $pt, Paths $paths, int $pathId, int $nbHits): Point
    {
        $x = $pt->x;
        $y = $pt->y;
        while ($nbHits - 1) {
            $newPt = new Point($x, --$y);
            $tile = new Tile($newPt, '^');
            $paths->addTile($tile, $pathId);
            --$nbHits;
        }

        return new Point($x, --$y);
    }

}

class Paths
{

    public $tiles;
    public $id = 0;
    private $paths = [];
    private $completedPaths = [];

    public function clonePath(int $id): int
    {
        ++$this->id;
        $this->paths[$this->id] = $this->paths[$id] ?? [];
        //echoLN("cloned to #".$this->id);

        return $this->id;
    }

    public function addTile(Tile $tile, int $id)
    {
        $this->paths[$id][$tile->getStringPoint()] = $tile;
    }

    public function setCompleted(int $id)
    {
        $this->completedPaths[] = $id;
    }

    public function getCompletedPaths(): array
    {
        $paths = [];
        foreach ($this->completedPaths as $id) {
            $paths[] = $this->paths[$id];
        }
        //echoLN($this);die();
        return $paths;
    }

    public function hasPoint(Point $pt, int $pathId): bool
    {
        return isset($this->paths[$pathId]["$pt"]);
    }

    public function __toString()
    {
        foreach ($this->completedPaths as $id) {
            $paths[] = implode(",\n", $this->paths[$id]);
        }
        return implode("\n-----\n", $paths);
    }

}

$grid = new Grid($width, $height, $rows);
$pathsDrawer = new PathsDrawer($grid);
$pathFinder = new PathFinder($grid, $pathsDrawer);
$newGrid = $pathFinder->drawValidPaths();
echo $newGrid->render() . "\n";

?>
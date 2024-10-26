<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OGame\Models\BuildingQueue;
use OGame\Models\Planet;
use OGame\Models\User;
use OGame\Services\BuildingQueueService;
use OGame\Services\ObjectService;
use Tests\UnitTestCase;

class BuildingQueueServiceTest extends UnitTestCase
{
    use DatabaseTransactions;

    protected BuildingQueueService $building_queue;

    protected User $player;

    /**
     * Set up common test components.
     *
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $object_service = new ObjectService();
        $this->building_queue = new BuildingQueueService($object_service);

        $planet = Planet::factory()->make(['id' => 1]);
        $this->planetService->setPlanet($planet);

        $this->player = User::factory()->make(['id' => 1]);
        $this->playerService->load(1);
    }

    /**
     * Tests object is found from building queue 
     */
    public function testIsObjectInBuildingQueue(): void
    {
        $planet = Planet::factory()->make(['id' => 1]);
        $this->planetService->setPlanet($planet);

        // Add level 3 shipyard to building queue
        $queue = new BuildingQueue();
        $queue->planet_id = $this->planetService->getPlanetId();
        $queue->object_id = 21;
        $queue->object_level_target = 3;
        $queue->save();

        $this->assertTrue($this->building_queue->objectInBuildingQueue($this->planetService, 'shipyard', 3));
        $this->assertFalse($this->building_queue->objectInBuildingQueue($this->planetService, 'shipyard', 4));
        $this->assertFalse($this->building_queue->objectInBuildingQueue($this->planetService, 'robot_factory', 3));
    }

    /**
     * Tests building queue item is cancelled if requirements are not met.
     */
    public function testCancelItemMissingRequirements(): void
    {
        // Add level 2 robot factory to building queue
        $queue_robot_factory = new BuildingQueue();
        $queue_robot_factory->planet_id = $this->planetService->getPlanetId();
        $queue_robot_factory->object_id = 14;
        $queue_robot_factory->object_level_target = 2;
        $queue_robot_factory->save();

        // Add level 1 shipyard to building queue
        $queue = new BuildingQueue();
        $queue->planet_id = $this->planetService->getPlanetId();
        $queue->object_id = 21;
        $queue->object_level_target = 1;
        $queue->save();

        // Assert that shipyard is in building queue
        $this->assertTrue($this->building_queue->objectInBuildingQueue($this->planetService, 'shipyard', 1));

        // Cancel robot factory
        $this->building_queue->cancel($this->planetService, $queue_robot_factory->id, 14);
        $this->building_queue->cancelItemMissingRequirements($this->planetService);

        // Assert that shipyard is in building queue
        $this->assertFalse($this->building_queue->objectInBuildingQueue($this->planetService, 'shipyard', 1));
    }
}

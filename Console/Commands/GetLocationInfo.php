<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Location\LocationService;

class GetLocationInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logistics:area:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get Location Info';

    private $locationService;

    /**
     * Create a new command instance.
     *
     * @param LocationService $locationService
     */
    public function __construct(LocationService $locationService)
    {
        parent::__construct();
        $this->locationService = $locationService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sichuan = ['四川省'];
        $chengdu = ['成都市'];
        $quxian  = [
            "锦江区","青羊区","金牛区","武侯区","成华区","龙泉驿区","青白江区","新都区","温江区",
            "金堂县","双流区","郫县","大邑县","蒲江县","新津县","简阳市","都江堰市","彭州市","邛崃市","崇州市"
        ];
        // 数据库写入信息
        $this->locationService->handleLocationInfo($sichuan);
        $this->locationService->handleLocationInfo($chengdu);
        $this->locationService->handleLocationInfo($quxian);

    }
}

<?php

namespace Eccube\Tests\Util;

use Eccube\Util\Cache;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Symfony\Component\Finder\Finder;

/**
 * Cache test cases.
 *
 * @author Kentaro Ohkouchi
 */
class CacheTest extends \PHPUnit_Framework_TestCase
{
    private $actual;
    private $expected;
    private $app;
    private $root;
    private $dirs;

    public function setUp()
    {
        // 仮想ファイルを生成
        $this->root = vfsStream::setup('rootDir');
        $this->dirs = array('doctrine', 'profiler', 'twig');
        $this->app = array(
            'config' => array(
                'root_dir' => vfsStream::url('rootDir')
            )
        );
        mkdir($this->app['config']['root_dir'].'/app/cache', 0777, true);
        file_put_contents($this->app['config']['root_dir'].'/app/cache/.gitkeep', 'test');
        // ランダムなファイルを生成しておく
        foreach ($this->dirs as $dir) {
            mkdir($this->app['config']['root_dir'].'/app/cache/'.$dir, 0777, true);
            $n = mt_rand(5, 10);
            for ($i = 0; $i < $n; $i++) {
                file_put_contents($this->app['config']['root_dir'].'/app/cache/'.$dir.'/'.$i, 'test');
            }
        }

        $this->app['config']['plugin_temp_realdir'] = $this->app['config']['root_dir'].'/app/cache/plugin';
    }

    public function testClearAll()
    {
        // .gitkeep を残してすべてを削除
        Cache::clear($this->app, true);

        $finder = new Finder();
        $iterator = $finder
            ->ignoreDotFiles(false)
            ->in($this->app['config']['root_dir'].'/app/cache')
            ->files();

        foreach ($iterator as $fileinfo) {
            $this->assertStringEndsWith('.gitkeep', $fileinfo->getPathname(), '.gitkeep しか存在しないはず');
        }
        $this->assertTrue($this->root->hasChild('app/cache/.gitkeep'), '.gitkeep は存在するはず');
    }

    public function testClear()
    {
        file_put_contents($this->app['config']['root_dir'].'/app/cache/.dummykeep', 'test');
        // 'doctrine', 'profiler', 'twig' ディレクトリを削除
        Cache::clear($this->app, false);

        $finder = new Finder();
        $iterator = $finder
            ->ignoreDotFiles(false)
            ->in($this->app['config']['root_dir'].'/app/cache')
            ->files();

        foreach ($iterator as $fileinfo) {
            $this->assertStringEndsWith('keep', $fileinfo->getPathname(), 'keep しか存在しないはず');
        }
        $this->assertTrue($this->root->hasChild('app/cache/.gitkeep'), '.gitkeep は存在するはず');
        $this->assertTrue($this->root->hasChild('app/cache/.dummykeep'), '.dummykeep は存在するはず');
    }

    public function testClearPluginCache()
    {
        mkdir($this->app['config']['plugin_temp_realdir'], 0777, true);
        file_put_contents($this->app['config']['plugin_temp_realdir'].'/config_cache.php', '<?php return array();');

        $this->assertTrue(file_exists($this->app['config']['plugin_temp_realdir'].'/config_cache.php'));

        Cache::clear($this->app, false);

        $this->assertFalse(file_exists($this->app['config']['plugin_temp_realdir'].'/config_cache.php'));
    }
}

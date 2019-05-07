<?php

namespace FRohlfing\Artifact\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ArtifactCreateCommand extends Command
{
    /**
     * Exit Codes.
     */
    const EXIT_SUCCESS = 0;
    const EXIT_FAILURE = 1;

    /**
     * The name and signature of the console command.
     *
     * Inherited options:
     *   -h, --help            Display this help message
     *   -q, --quiet           Do not output any message
     *   -V, --version         Display this application version
     *       --ansi            Force ANSI output
     *       --no-ansi         Disable ANSI output
     *   -n, --no-interaction  Do not ask any interactive question
     *       --env[=ENV]       The environment the command should run under
     *   -v|vv|vvv, --verbose  Increase the verbosity of messages
     *
     * @var string
     */
    protected $signature = 'artifact:create
                            { --p|package=  : Package name (format: <vendor>/<package>) <comment>[default: all]</comment> }
                            { --o|overwrite : Overwrite any existing archives }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create archives for packages that the project uses.';

    /**
     * Create a new console command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $packages = $this->getPackages();
            $package = $this->option('package');
            if ($package !== null) {
                $package = array_first($packages, function ($item) use($package) {
                    return $item->name === $package;
                });
                if ($package === null) {
                    throw new Exception('Package not found');
                }
                $this->createArchive(trim($package->name, '/'), $package->version);
            }
            else {
                foreach ($packages as $package) {
                    $this->createArchive($package->name, $package->version);
                }
            }
        }
        catch (Exception $e) {
            $this->error($e->getMessage());
            return static::EXIT_FAILURE;
        }

        $this->info('Local repository created successfully!');

        return static::EXIT_SUCCESS;
    }

    /**
     * Create the archiv for the given package
     * @param string $package
     * @param string $version
     */
    private function createArchive($package, $version)
    {
        $this->write("Write archive $package... ");

        // destination path
        $path = rtrim(config('artifact.path'), '/') ;
        if (!file_exists($path)) {
            mkdir($path, 777, true);
        }

        $archive = str_replace('/', '_', $package) . '_' . $version . '.zip';
        if (file_exists($path . '/' . $archive)) {
            if ($this->option('overwrite')) {
                @unlink($path . '/' . $archive);
            }
            else {
                $this->writeRed("already exists\n");
                return;
            }

        }

        // package folder
        $source = base_path('vendor/' . $package);

        // create archive
        $zip = new ZipArchive;
        if ($zip->open($path . '/' . $archive, ZipArchive::CREATE) === true) {
            try {
                $this->addWholeFolderToArchive($zip, $source);

                // add version to the package's composer.json
                $composerFile = $source . '/composer.json';
                if (!($composerFileContent = file_get_contents($composerFile))) {
                    $this->writeRed("composer.json not found\n");
                    return;
                }
                $composerJson = json_decode($composerFileContent);
                if (!isset($composerJson->version)) {
                    $composerJson->version = $version;
                    if (!$zip->deleteName('composer.json')) {
                        $this->writeRed("Could not delete composer.json from archive\n");
                        return;
                    }
                    if (!$zip->addFromString('composer.json', json_encode($composerJson, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES))) {
                        $this->writeRed("Could not add composer.json to archive\n");
                        return;
                    }
                }
            }
            finally {
                $zip->close();
            }
        }

        $this->writeGreen("ok\n");
    }

    /**
     * Add a whole folder to a given archive.
     *
     * @param ZipArchive $zip
     * @param $folder
     */
    private function addWholeFolderToArchive(ZipArchive $zip, $folder)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder),RecursiveIteratorIterator::LEAVES_ONLY);
        $offset = strlen($folder) + 1;
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, $offset);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Get the packages that the project uses.
     *
     * @return array
     * @throws Exception
     */
    private function getPackages()
    {
        $packages = [];
        $composerLockFile = base_path('composer.lock');
        if (!($content = file_get_contents($composerLockFile))) {
            $this->writeRed("composer.lock of the project not found\n");
            return [];
        }
        $lock = json_decode($content, true);
        foreach ($lock['packages'] as $package) {
            if (stripos($package['version'], 'v') === 0) {
                $package['version'] = substr($package['version'], 1);
            }
            $packages[] = (object)[
                'name' => $package['name'],
                'version' => $package['version'],
            ];
        }

//        foreach (scandir(base_) as $vendor) {
//            if (is_dir($vendor) && !in_array($vendor,  ['.', '..', 'bin', 'composer'])) {
//                foreach (scandir($path . '/' . $vendor) as $package) {
//                    if ($package !== '.' && $package !== '..') {
//                        $packages[] = $vendor . '/' .$package;
//                    }
//                }
//            }
//        }

        return $packages;
    }

    /**
     * Write a string as information output (white font).
     *
     * @param string $string
     */
    private function write($string)
    {
        $this->output->write($string);
    }

    /**
     * Write a string as information output (green font).
     *
     * @param string $string
     */
    private function writeGreen($string)
    {
        $this->output->write("<info>$string</info>");
    }

    /**
     * Write a string as error output.
     *
     * @param string $string
     */
    private function writeRed($string)
    {
        $this->output->write("<error>$string</error>");
    }
}

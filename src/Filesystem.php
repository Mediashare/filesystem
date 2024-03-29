<?php

namespace Kzu\Filesystem;

use Kzu\Security\Crypto;

Trait Filesystem {
    static public $crypto_secret;

    static public function find(string $directory, ?array $extensions = []): ?array {
        foreach (glob($directory) ?? [] as $file):
            if (is_file($file)):
                if (empty($extensions) 
                    || (!empty(pathinfo($file)['extension']) 
                        && in_array(pathinfo($file)['extension'], $extensions))):
                    $files[] = $file;
                endif;
            elseif ($file !== '.' && $file !== '..' && file_exists($file) && is_dir($file)):
                $files = array_merge($files ?? [], Filesystem::find($file.'/*', $extensions));
            endif;
        endforeach;
        return $files ?? [];
    }

    static public function read(string $filepath): ?string {
        $filepath = str_replace("/", DIRECTORY_SEPARATOR, $filepath);
        
        if (!file_exists($filepath)): return null; endif;
        $content = file_get_contents($filepath);
        if (!empty(pathinfo($filepath)['extension']) && pathinfo($filepath)['extension'] === 'encrypted'):
            $content = Crypto::decrypt($content, Filesystem::$crypto_secret);
        endif;
        return $content ?? "";
    }

    static public function write(string $filepath, ?string $content = "", ?bool $encrypted = false): bool {
        $filepath = str_replace("/", DIRECTORY_SEPARATOR, $filepath);
        
        if (!file_exists($directory = dirname($filepath))):
            Filesystem::mkdir($directory);
        endif;
        
        if ($encrypted):
            if (!empty(pathinfo($filepath)['extension']) && pathinfo($filepath)['extension'] !== 'encrypted'):
                $filepath = $filepath.".encrypted";
            endif;
            $content = Crypto::encrypt($content, Filesystem::$crypto_secret);
        elseif (!empty(pathinfo($filepath)['extension']) && pathinfo($filepath)['extension'] === 'encrypted'):
            $filepath = str_replace('.encrypted', '', $filepath);
        endif;

        $file = fopen($filepath, "w");
        fwrite($file, $content);
        fclose($file);
        return $filepath;
    }

    static public function mkdir($directory): bool {
        $directory = str_replace("/", DIRECTORY_SEPARATOR, $directory);
        
        if (!file_exists($directory)):
            if (!mkdir($directory)): Filesystem::mkdir(dirname($directory)); endif;
        endif;    
        return true;
    }

    static public function delete(string $filepath): bool {
        $filepath = str_replace("/", DIRECTORY_SEPARATOR, $filepath);
        
        if (file_exists($filepath)):
            return unlink($filepath) ?? false;
        else: return false; endif;
    }
}

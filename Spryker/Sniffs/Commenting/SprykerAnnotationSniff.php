<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Checks if Spryker method annotations use interfaces where applicable/needed.
 */
class SprykerAnnotationSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CLASS,
            T_INTERFACE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isSprykerNamespace($phpCsFile)) {
            return;
        }

        $this->checkAnnotations($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function checkAnnotations(File $phpCsFile, int $stackPointer): void
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            return;
        }
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];
        if (!$docBlockStartIndex) {
            return;
        }

        $methodAnnotations = $this->getFixableMethodAnnotations($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);
        if (!$methodAnnotations) {
            return;
        }

        foreach ($methodAnnotations as $methodAnnotation) {
            $fixable = $phpCsFile->addFixableError(
                sprintf('Interface must be used for %s annotation of %s', $methodAnnotation['method'], $methodAnnotation['class']),
                $methodAnnotation['index'],
                'Annotation.Interface.Invalid'
            );
            if (!$fixable) {
                continue;
            }

            $content = $methodAnnotation['interface'] . ' ' . $methodAnnotation['method'];

            $phpCsFile->fixer->beginChangeset();
            $phpCsFile->fixer->replaceToken($methodAnnotation['index'] + 2, $content);
            $phpCsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return array
     */
    protected function getFixableMethodAnnotations(
        File $phpCsFile,
        int $docBlockStartIndex,
        int $docBlockEndIndex
    ): array {
        $tokens = $phpCsFile->getTokens();

        $path = $this->findBasePath($phpCsFile->getFilename());
        if (!$path) {
            // We cannot fix it then for now
            return [];
        }

        $annotations = [];
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_TAG || $tokens[$i]['content'] !== '@method') {
                continue;
            }

            // T_DOC_COMMENT_STRING invalid, another sniff should check on this
            if (strpos($tokens[$i + 2]['content'], ' ') === false) {
                continue;
            }

            [$class, $method] = explode(' ', $tokens[$i + 2]['content'], 2);

            $ignoredMethods = [
                'getFactory()',
                'getConfig()',
                'getSharedConfig()',
                'getApplication()',
            ];
            if (in_array($method, $ignoredMethods, true)) {
                continue;
            }

            if (substr($class, -9) === 'Interface' || substr($class, 0, 5) === '\\Orm\\') {
                continue;
            }
            if (!preg_match('/^[A-Z]/', $class)) {
                continue;
            }
            if ($this->isAbstract($class)) {
                continue;
            }

            $interface = $class . 'Interface';
            $interfacePathElement = str_replace('\\', DIRECTORY_SEPARATOR, $interface);
            $interfacePath = $path . $interfacePathElement . '.php';

            if (!file_exists($interfacePath)) {
                $phpCsFile->addError(
                    sprintf('Interface missing for %s annotation of %s', $method, $class),
                    $i,
                    'Annotation.Interface.Missing'
                );

                continue;
            }

            $annotation = [
                'index' => $i,
                'method' => $method,
                'class' => $class,
                'interface' => $interface,
            ];

            $annotations[] = $annotation;
        }

        return $annotations;
    }

    /**
     * @param string $path
     *
     * @return string|null
     */
    protected function findBasePath(string $path): ?string
    {
        preg_match('#^.+/(vendor|spryker)/.+/src/#', $path, $matches);
        if (!$matches) {
            return null;
        }

        return rtrim($matches[0], DIRECTORY_SEPARATOR);
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    protected function isAbstract(string $class): bool
    {
        $classPieces = explode('\\', $class);

        $className = array_pop($classPieces);

        return (substr($className, 0, 8) === 'Abstract');
    }
}

<?php

namespace Pyz\Zed\Sales\Communication\Controller;

class IndexController
{
    public function saveAction(int $count): void
    {
        $this->addSuccessMessage(sprintf('Saved %d items', $count));
        $this->addErrorMessage(\sprintf('Failed for %d items', $count));

        $this->addInfoMessage('Queued %count% items', ['%count%' => $count]);

        $formatted = sprintf('Saved %d items', $count);
    }
}

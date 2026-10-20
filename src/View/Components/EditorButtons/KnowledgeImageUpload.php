<?php

namespace TeamNiftyGmbH\NuxbeKnowledge\View\Components\EditorButtons;

use FluxErp\View\Components\EditorButtons\ImageUpload;

class KnowledgeImageUpload extends ImageUpload
{
    public static function scopes(): array
    {
        return ['knowledge'];
    }
}

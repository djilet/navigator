<?php

use Phinx\Migration\AbstractMigration;

class CreateAdsFbUtmTable extends AbstractMigration
{
    public function change()
    {
        $users = $this->table('ads_fb_utm');
        $users->addColumn('ads_campaign_id', 'string', ['limit' => 255])
            ->addColumn('advert_id', 'string', ['limit' => 255])
            ->addColumn('utm_source', 'string', ['limit' => 255])
            ->addColumn('utm_medium', 'string', ['limit' => 255])
            ->addColumn('utm_campaign', 'string', ['limit' => 255])
            ->addColumn('utm_term', 'string', ['limit' => 255])
            ->addColumn('utm_content', 'string', ['limit' => 255])
            ->create();
    }
}

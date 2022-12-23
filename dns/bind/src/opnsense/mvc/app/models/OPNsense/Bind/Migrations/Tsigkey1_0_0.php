<?php

/**
 *    Copyright (C) 2022 Robbert Rijkse
 *
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace OPNsense\Bind\Migrations;

use OPNsense\Base\BaseModelMigration;
use OPNsense\Core\Config;

class Tsigkey1_0_0 extends BaseModelMigration
{
    /**
    * Migrate older keys into new model
    * @param $model
    */
    public function run($model)
    {
        $config = Config::getInstance()->object();

        if (empty($config->OPNsense->bind)) {
            return;
        }

        $bindConfig = $config->OPNsense->bind;

        $keyNames = [];
        if (!empty($bindConfig->tsigkey->tsig_keys->tsig_key)) {
            foreach ($bindConfig->tsigkey->tsig_keys->tsig_key as $key) {
                array_push($keyNames, $key->name);
            }
        }

        if (!empty($bindConfig->domain->domains->domain)) {
            foreach ($bindConfig->domain->domains->domain as $domain) {
                if (!empty($domain->transferkeyname)) {
                    if (!in_array($domain->transferkeyname, $keyNames)){
                        $keyNode = $model->tsig_keys->tsig_key->add();
                        $keyNode->setNodes(
                            [
                                'enabled' => 1,
                                'algo' => $domain->transferkeyalgo,
                                'name' => $domain->transferkeyname,
                                'secret' => $domain->transferkey,
                            ]
                        );
                    }
                }
            }
        }

        # Temporarily here for testing so the version number doesn't increase incase it does actually work
        trigger_error("Test",E_USER_ERROR);
    }
}

<?php
/**
 * Copyright 2014 Google Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 Google Inc. All rights reserved
 * @license http://www.apache.org/licenses/LICENSE-2.0.txt Apache-2.0
 * @package Analyzer
 * @subpackage AstProcessor
 */

namespace ReckiCT\Analyzer\AstProcessor;

use PhpParser\NodeVisitorAbstract;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Function_;

class RecursionResolver extends NodeVisitorAbstract
{
    protected $function;
    protected $scopeStack = array();

    /**
     * Called when entering a node.
     *
     * Return value semantics:
     *  * null:      $node stays as-is
     *  * otherwise: $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return null|Node Node
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Function_) {
            $this->scopeStack[] = $this->function;
            $this->function = $node;
        }

        return null;
    }

    /**
     * Called when leaving a node.
     *
     * Return value semantics:
     *  * null:      $node stays as-is
     *  * false:     $node is removed from the parent array
     *  * array:     The return value is merged into the parent array (at the position of the $node)
     *  * otherwise: $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return null|Node|false|Node[] Node
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Function_) {
            $this->function = array_pop($this->scopeStack);

            return;
        }
        if (!$node instanceof FuncCall) {
            return;
        }
        if (!$this->function || !$node->name instanceof Name) {
            return;
        }
        if ($node->name->toString() == $this->function->namespacedName->toString()
            || $node->name->toString() == $this->function->namespacedName->getLast()) {
            /**
             * @todo This check isn't enough, since PHP has "fallback" behavior for functions. We also need to check
             *        the namespace names to see if they are used as well.
             */
            $node->isSelfRecursive = true;
        }
    }

}

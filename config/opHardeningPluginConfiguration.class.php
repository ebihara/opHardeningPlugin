<?php

/**
 * This file is part of the opHardeningPlugin package.
 * (c) Kousuke Ebihara (http://co3k.org/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

class opHardeningPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    $this->dispatcher->connect('context.load_factories', array($this, 'appendDefaultSafeguards'));
  }

  public function appendDefaultSafeguards(sfEvent $event)
  {
    $context = $event->getSubject();

    $this->appendSafeguard(null, 'use_http_only_session_cookie', $context);
    $this->appendSafeguard(null, 'force_encoding_to_UTF8', $context);
    $this->appendSafeguard(null, 'allow_complex_password', $context);

    $this->appendSafeguard('op_action.post_execute', 'disable_content_sniffing', $context);
    $this->appendSafeguard('op_action.post_execute', 'deny_non_same_origin_frame', $context);
    $this->appendSafeguard('op_action.post_execute', 'enable_XSS_filter_with_block', $context);
    $this->appendSafeguard('response.filter_content', 'JSON_hijacking_protection', $context);
    $this->appendSafeguard('response.filter_content', 'escape_html_in_JSON', $context);
  }

  protected function appendSafeguard($eventName, $safeguardName, sfContext $context = null)
  {
    if (!$context && sfContext::hasInstance())
    {
      $context = sfContext::getInstance();
    }

    $configurations = $this->extractSafeguardConfigurations($safeguardName, sfConfig::get('op_hardening', array()));

    $className = 'op'.sfInflector::camelize($safeguardName).'Safeguard';

    $safeguard = new $className($context, $configurations);
    if ($this->checkRegisterableSafeguard($safeguard))
    {
      if ($eventName)
      {
        $this->dispatcher->connect($eventName, array($safeguard, 'apply'));
      }
      else
      {
        $safeguard->apply(new sfEvent(null, ''));
      }
    }
  }

  protected function checkRegisterableSafeguard(opSafeguardInterface $safeguard)
  {
    return ($safeguard->isAvailable() && $safeguard->getConfig()->get('enabled', true));
  }

  protected function extractSafeguardConfigurations($safeguardName, array $configurations = array())
  {
    if (empty($configurations[$safeguardName]))
    {
      return array();
    }

    return $configurations[$safeguardName];
  }
}

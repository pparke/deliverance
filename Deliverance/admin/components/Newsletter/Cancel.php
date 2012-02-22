<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Deliverance/DeliveranceList.php';
require_once 'Deliverance/dataobjects/DeliveranceNewsletter.php';

/**
 * Confirmation page for cancelling a scheduled newsletter.
 *
 * @package   Deliverance
 * @copyright 2011-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class DeliveranceNewsletterCancel extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var DeliveranceNewsletter
	 */
	protected $newsletter;

	/**
	 * @var DeliveranceList
	 */
	protected $list;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->loadFromXML(dirname(__FILE__).'/cancel.xml');

		parent::initInternal();

		$this->initNewsletter();
		$this->initList();
	}

	// }}}
	// {{{ protected function initNewsletter()

	protected function initNewsletter()
	{
		if ($this->id == '') {
			$this->relocate('Newsletter');
		}

		$this->newsletter = new Newsletter();
		$this->newsletter->setDatabase($this->app->db);
		if (!$this->newsletter->load($this->id)) {
			throw new AdminNotFoundException(sprintf(
				'A newsletter with the id of ‘%s’ does not exist',
				$this->id));
		}

		// Can't cancel a newsletter that has been scheduled.
		if (!$this->newsletter->isScheduled()) {
			$this->relocate();
		}

		// Can't cancel a newsletter that has been sent.
		if ($this->newsletter->isSent()) {
			$this->relocate();
		}
	}

	// }}}
	// {{{ protected function initList()

	protected function initList()
	{
		$this->list = new MailChimpList($this->app);
		$this->list->setTimeout(
			$this->app->config->mail_chimp->admin_connection_timeout);
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$campaign = $this->newsletter->getCampaign($this->app);
		$this->list->unscheduleCampaign($campaign);

		$this->newsletter->send_date = null;
		$this->newsletter->save();

		$message = new SwatMessage(sprintf(
			Deliverance::_('The delivery of “%s” has been canceled.'),
			$this->newsletter->subject
		));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{  protected function relocate()

	protected function relocate()
	{
		$this->app->relocate(sprintf('Newsletter/Details?id=%s',
			$this->newsletter->id));
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$message = $this->ui->getWidget('content_block');
		$message->content = $this->getMessage();
		$message->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->popEntry();

		$title = $this->newsletter->getCampaignTitle();
		$link  = sprintf('Newsletter/Details?id=%s', $this->newsletter->id);
		$this->navbar->createEntry($title, $link);

		$this->navbar->createEntry(Delieverance::_('Cancel Delivery'));
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
		$frame = $this->ui->getWidget('edit_frame');
		$frame->title = Deliverance::_('Cancel Delivery');
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
	}

	// }}}
	// {{{ protected function getMessage()

	protected function getMessage()
	{
		$message = sprintf('<p>%s</p><p>%s/p>',
			Deliverance::_('The delivery of “%ss” will canceled.'),
			Deliverance::_('The newsletter won’t be deleted and can be '.
			'rescheduled for a later delivery date.'));

		return sprintf($message, $this->newsletter->subject);
	}

	// }}}
}

?>
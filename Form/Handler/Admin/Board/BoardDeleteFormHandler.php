<?php

/*
 * This file is part of the CCDNForum ForumBundle
 *
 * (c) CCDN (c) CodeConsortium <http://www.codeconsortium.com/>
 *
 * Available on github <http://www.github.com/codeconsortium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CCDNForum\ForumBundle\Form\Handler\Admin\Board;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher ;

use Doctrine\Common\Collections\ArrayCollection;

use CCDNForum\ForumBundle\Component\Dispatcher\ForumEvents;
use CCDNForum\ForumBundle\Component\Dispatcher\Event\AdminBoardEvent;
use CCDNForum\ForumBundle\Form\Handler\BaseFormHandler;
use CCDNForum\ForumBundle\Model\Model\ModelInterface;
use CCDNForum\ForumBundle\Entity\Board;

/**
 *
 * @category CCDNForum
 * @package  ForumBundle
 *
 * @author   Reece Fowell <reece@codeconsortium.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  Release: 2.0
 * @link     https://github.com/codeconsortium/CCDNForumForumBundle
 *
 */
class BoardDeleteFormHandler extends BaseFormHandler
{
    /**
     *
     * @access protected
     * @var \CCDNForum\ForumBundle\Form\Type\Admin\Board\BoardDeleteFormType $boardDeleteFormType
     */
    protected $boardDeleteFormType;

    /**
     *
     * @access protected
     * @var \CCDNForum\ForumBundle\Model\Model\BoardModel $boardModel
     */
    protected $boardModel;

    /**
     *
     * @access protected
     * @var \CCDNForum\ForumBundle\Entity\Board $board
     */
    protected $board;

    /**
     *
     * @access public
     * @param \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher  $dispatcher
     * @param \Symfony\Component\Form\FormFactory                                        $factory
     * @param \CCDNForum\ForumBundle\Form\Type\Admin\Board\BoardDeleteFormType           $boardDeleteFormType
     * @param \CCDNForum\ForumBundle\Model\Model\BoardModel                              $boardModel
     */
    public function __construct(ContainerAwareEventDispatcher  $dispatcher, FormFactory $factory, $boardDeleteFormType, ModelInterface $boardModel)
    {
        $this->factory = $factory;
        $this->boardDeleteFormType = $boardDeleteFormType;
        $this->boardModel = $boardModel;
        $this->dispatcher = $dispatcher;
    }

    /**
     *
     * @access public
     * @param  \CCDNForum\ForumBundle\Entity\Board                                    $board
     * @return \CCDNForum\ForumBundle\Form\Handler\Admin\Board\BoardDeleteFormHandler
     */
    public function setBoard(Board $board)
    {
        $this->board = $board;

        return $this;
    }

    /**
     *
     * @access public
     * @return \Symfony\Component\Form\Form
     */
    public function getForm()
    {
        if (null == $this->form) {
            if (!is_object($this->board) && !$this->board instanceof Board) {
                throw new \Exception('Board object must be specified to delete.');
            }

            $this->dispatcher->dispatch(ForumEvents::ADMIN_BOARD_DELETE_INITIALISE, new AdminBoardEvent($this->request, $this->board));

            $this->form = $this->factory->create($this->boardDeleteFormType, $this->board);
        }

        return $this->form;
    }

    /**
     *
     * @access protected
     * @param  \CCDNForum\ForumBundle\Entity\Board           $board
     * @return \CCDNForum\ForumBundle\Model\Model\BoardModel
     */
    protected function onSuccess(Board $board)
    {
        $this->dispatcher->dispatch(ForumEvents::ADMIN_BOARD_DELETE_SUCCESS, new AdminBoardEvent($this->request, $board));

        if (! $this->form->get('confirm_subordinates')->getData()) {
            $topics = new ArrayCollection($board->getTopics()->toArray());

            $this->boardModel->reassignTopicsToBoard($topics, null)->flush();
        }

        $this->boardModel->deleteBoard($board);

        $this->dispatcher->dispatch(ForumEvents::ADMIN_BOARD_DELETE_COMPLETE, new AdminBoardEvent($this->request, $board));

        return $this->boardModel;
    }
}

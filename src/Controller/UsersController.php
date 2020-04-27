<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        // Configure the login action to not require authentication, preventing
        // the infinite redirect loop issue
        $this->Authentication->addUnauthenticatedActions(['autoadd']);
    }
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Ministries', 'Roles'],
        ];
        $users = $this->paginate($this->Users);
        // This doesn't work 
        // https://discourse.cakephp.org/t/authentication-index/7506/2
        //
        $this->Authorization->authorize($users);

        $this->set(compact('users'));
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => ['Ministries', 'Roles', 'Activities', 'Competencies', 'Pathways'],
        ]);
        $this->Authorization->authorize($user);

        $this->set('user', $user);
    }


    /**
     * User auto-add method.
     * This is the controller that we redirect to when we detect a user who
     * doesn't already have an account; in other words, they've gone through the
     * SiteMinder authentication already, there's a valid REMOTE_USER environment
     * variable set, but it doesn't match anything in the system yet. All we do 
     * is create a new user with the IDIR field set to the REMOTE_USER and then
     * redirect to the users/home page.
     * #TODO this should have a LDAP/GAL lookup to grab the user's name and email 
     * address at a minimum; lookup ministry as well? or perhaps, if that's 
     * problematic, we could use the existing login form, with the IDIR as a 
     * hidden field
     * #TODO remove password field? for now it's just hard-coded as the same thing
     * for every user lol
     *
     * @return \Cake\Http\Response|null Redirects on successful add, throws error on fail
     */
    public function autoadd()
    {
        $this->request->allowMethod(['get']);
        $this->Authorization->skipAuthorization();
        $user = $this->Users->newEmptyEntity();
        $user->name = 'Learner';
        $user->idir = env('REMOTE_USER');
        $user->ministry_id = 1;
        $user->role_id = 1;
        $user->email = 'learner@gov.bc.ca';
        $user->password = 'learning';

        if ($this->Users->save($user)) {
            return $this->redirect(['action' => 'home']);
        } else {
            $this->Flash->error(__('Something went wrong when creating your account. Please contact learningagent@gov.bc.ca for assistance.'));
        }
        

    }



    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        $this->Authorization->authorize($user);
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $ministries = $this->Users->Ministries->find('list', ['limit' => 200]);
        $roles = $this->Users->Roles->find('list', ['limit' => 200]);
        $activities = $this->Users->Activities->find('list', ['limit' => 200]);
        $competencies = $this->Users->Competencies->find('list', ['limit' => 200]);
        $pathways = $this->Users->Pathways->find('list', ['limit' => 200]);
        $this->set(compact('user', 'ministries', 'roles', 'activities', 'competencies', 'pathways'));

    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => ['Activities', 'Competencies', 'Pathways'],
        ]);
        $this->Authorization->authorize($user);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect($this->referer());
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $ministries = $this->Users->Ministries->find('list', ['limit' => 200]);
        $roles = $this->Users->Roles->find('list', ['limit' => 200]);
        $activities = $this->Users->Activities->find('list', ['limit' => 200]);
        $competencies = $this->Users->Competencies->find('list', ['limit' => 200]);
        $pathways = $this->Users->Pathways->find('list', ['limit' => 200]);
        $this->set(compact('user', 'ministries', 'roles', 'activities', 'competencies', 'pathways'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Login method
     *
     * @param 
     * @return \Cake\Http\Response|null Redirects to user home page.
     * @throws 
     */
    public function login() 
    {

        $this->Authorization->skipAuthorization();
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();
        // regardless of POST or GET, redirect if user is logged in
        if ($result->isValid()) {
            // redirect to /pages/home after login success
            $redirect = $this->request->getQuery('redirect', [
                'controller' => 'Users',
                'action' => 'home',
            ]);

            return $this->redirect($redirect);
        }
        // display error if user submitted and authentication failed
        if ($this->request->is('post') && !$result->isValid()) {
            $this->Flash->error(__('Invalid username'));
        }
    }

    /**
     * Logout method
     *
     * @param 
     * @return \Cake\Http\Response|null Redirects to login page.
     * @throws 
     */
    public function logout()
    {

        $this->Authorization->skipAuthorization();
        $result = $this->Authentication->getResult();
        // regardless of POST or GET, redirect if user is logged in
        if ($result->isValid()) {
            $this->Authentication->logout();
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
    }

   /**
     * Home method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function home()
    {

	    $u = $this->request->getAttribute('authentication')->getIdentity();
        $user = $this->Users->get($u->id, [
            'contain' => ['Pathways', 
                            'Pathways.Categories', 
                            'Activities', 
                            'Activities.ActivityTypes',
                            'Competencies',
                            'Ministries'],
        ]);
        $this->Authorization->authorize($user);
        $this->set('user', $user);
    }

}

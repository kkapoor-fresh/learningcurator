<?php
declare(strict_types=1);

namespace App\Controller;

Use Cake\ORM\TableRegistry;


/**
 * Activities Controller
 *
 * @property \App\Model\Table\ActivitiesTable $Activities
 *
 * @method \App\Model\Entity\Activity[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ActivitiesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $this->Authorization->skipAuthorization();
        $this->paginate = [
            'contain' => ['Statuses', 'Ministries', 'Categories', 'ActivityTypes'],
        ];
        $activities = $this->paginate($this->Activities);

        $this->set(compact('activities'));
    }

    /**
     * View method
     *
     * @param string|null $id Activity id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->Authorization->skipAuthorization();
        // As we loop through the activities for the steps on this pathway, we 
        // need to be able to check to see if the current user has "claimed" 
        // that activity. Here we get the current user id and use it to select 
        // all of the claimed activities assigned to them, and then process out 
        // just the activity IDs into a simple array. Then, in the template 
        // code, we can simply  if(in_array($rj->activity->id,$useractivitylist
        //
        // First let's check to see if this person is logged in or not.
        //
	    $user = $this->request->getAttribute('authentication')->getIdentity();
        if(!empty($user)) {
            // We need create an empty array first. If nothing gets added to
            // it, so be it
            $useractivitylist = array();
            // Get access to the apprprioate table
            $au = TableRegistry::getTableLocator()->get('ActivitiesUsers');
            // Select based on currently logged in person
            $useacts = $au->find()->where(['user_id = ' => $user->id]);
            // convert the results into a simple array so that we can
            // use in_array in the template
            $useractivities = $useacts->toList();
            // Loop through the resources and add just the ID to the 
            // array that we will pass into the template
            foreach($useractivities as $uact) {
                array_push($useractivitylist, $uact['activity_id']);
            }
        }
        $activity = $this->Activities->get($id, [
            'contain' => ['Statuses', 'Ministries', 'Categories', 'ActivityTypes', 'Users', 'Competencies', 'Steps', 'Steps.Pathways', 'Tags'],
        ]);

        $this->set(compact('activity', 'useractivitylist'));
    }







    /**
     * Find method for activities; intended for use as an auto-complete
     *  search function for adding activities to steps
     *
     * @param string|null $search search pararmeters to lookup activities.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function find()
    {
        $this->Authorization->skipAuthorization();
	    $search = $this->request->getQuery('q');
        $activities = $this->Activities->find()->where(function ($exp, $query) use($search) {
            return $exp->like('name', '%'.$search.'%');
        })->order(['name' => 'ASC']);
        $this->set('activities', $activities);
    }







    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {

	$user = $this->request->getAttribute('authentication')->getIdentity();
        $activity = $this->Activities->newEmptyEntity();
        $this->Authorization->authorize($activity);
	if ($this->request->is('post')) {

            $activity = $this->Activities->patchEntity($activity, $this->request->getData());
            $activity->createdby_id = $user->id;
            $activity->modifiedby_id = $user->id;

            if ($this->Activities->save($activity)) {
                $this->Flash->success(__('The activity has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The activity could not be saved. Please, try again.'));
        }
        $statuses = $this->Activities->Statuses->find('list', ['limit' => 200]);
        $ministries = $this->Activities->Ministries->find('list', ['limit' => 200]);
        $categories = $this->Activities->Categories->find('list', ['limit' => 200]);
        $activityTypes = $this->Activities->ActivityTypes->find('list', ['limit' => 200]);
        $users = $this->Activities->Users->find('list', ['limit' => 200]);
        $competencies = $this->Activities->Competencies->find('list', ['limit' => 200]);
        $steps = $this->Activities->Steps->find('list', ['limit' => 200]);
        $tags = $this->Activities->Tags->find('list', ['limit' => 200]);
        $this->set(compact('activity', 'statuses', 'ministries', 'categories', 'activityTypes', 'users', 'competencies', 'steps', 'tags'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Activity id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $activity = $this->Activities->get($id, [
            'contain' => ['Users', 'Competencies', 'Steps', 'Tags'],
        ]);
        $this->Authorization->authorize($activity);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $activity = $this->Activities->patchEntity($activity, $this->request->getData());
            if ($this->Activities->save($activity)) {
                $this->Flash->success(__('The activity has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The activity could not be saved. Please, try again.'));
        }
        $statuses = $this->Activities->Statuses->find('list', ['limit' => 200]);
        $ministries = $this->Activities->Ministries->find('list', ['limit' => 200]);
        $categories = $this->Activities->Categories->find('list', ['limit' => 200]);
        $activityTypes = $this->Activities->ActivityTypes->find('list', ['limit' => 200]);
        $users = $this->Activities->Users->find('list', ['limit' => 200]);
        $competencies = $this->Activities->Competencies->find('list', ['limit' => 200]);
        $steps = $this->Activities->Steps->find('list', ['limit' => 200]);
        $tags = $this->Activities->Tags->find('list', ['limit' => 200]);
        $this->set(compact('activity', 'statuses', 'ministries', 'categories', 'activityTypes', 'users', 'competencies', 'steps', 'tags'));
    }


/**
* Like an activity
*
* @return \Cake\Http\Response|null Redirects to courses index.
* @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
*
*/
public function like ($id = null)
{
    $activity = $this->Activities->get($id);
    $this->Authorization->authorize($activity);
    $newlike = $activity->recommended;
    $newlike++;
    $this->request->getData()['recommended'] = $newlike;
    $activity->recommended = $newlike;
    if ($this->request->is(['get'])) {
        $activity = $this->Activities->patchEntity($activity, $this->request->getData());
        if ($this->Activities->save($activity)) {
            echo 'Liked!';
            //return $this->redirect($this->referer());
        } else {
            $this->Flash->error(__('The activity could not be saved. Please, try again.'));
        }
    }
}



    /**
     * Delete method
     *
     * @param string|null $id Activity id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $activity = $this->Activities->get($id);
        $this->Authorization->authorize($activity);
        if ($this->Activities->delete($activity)) {
            $this->Flash->success(__('The activity has been deleted.'));
        } else {
            $this->Flash->error(__('The activity could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }





public function activityImportUpload () 
{
	$fileobject = $this->request->getData('standardimportfile');
	$destination = '/home/allankh/learningagent/webroot/files/standard-import.csv';

	// Existing files with the same name will be replaced.
	$fileobject->moveTo($destination);
	return $this->redirect(['action' => 'activityImport']);
}

/**
* Learning Agent Standard Resources Import file
*
* @return \Cake\Http\Response|null Redirects to courses index.
* @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
*
*/
public function activityImport ()
{
	// 
    // 0-Pathway,1-Step,2-Activity Type,3-Name,4-Hyperlink,5-Description,6-Required,
    // 7-Competencies,8-Time,9-Tags,10-Licensing,11-ISBN,12-Curator
	//
	$now = date('Y-m-d H:i:s');
	//$who = $_SERVER["REMOTE_USER"];
	//$who = $this->request->env('REMOTE_USER');
	$desc = '';
	if (($handle = fopen("/home/allankh/learningagent/webroot/files/standard-import.csv", "r")) !== FALSE) {
		fgetcsv($handle);
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		$action = $this->Activities->newEmptyEntity();
		$action->name = utf8_encode($data[3]);
		$action->description = utf8_encode($data[5]);
		$action->moderator_notes = '';
		$action->hyperlink = $data[4];
		$action->licensing = '';
		$action->meta_description = '';
		$action->status_id = 1;
		$action->modifiedby_id = 1;
		$action->createdby_id = utf8_encode($data[12]);
        $action->approvedby_id = 1;
        $action->hours = utf8_encode($data[8]); // TODO change this to minutes instead of hours

        $reqd = 0;
        if($data[6] == 'y') $reqd = 1;
        $action->required = $reqd;

        //1-watch,2-read,3-listen,4-participate
        $actid = 1;
        if($data[2] == 'Watch') $actid = 1;
        if($data[2] == 'Read') $actid = 2;
        if($data[2] == 'Listen') $actid = 3;
        if($data[2] == 'Participate') $actid = 4;
        $action->activity_types_id = $actid;
        
		if ($this->Activities->save($action)) {
			//echo 'yeahyeah';
		} else {
			echo 'WTF';
		}
	}
	return $this->redirect(['action' => 'index']);
}
}

     /**
     * PeopleSoft ELM Learning System synchronization
     *
     * @return \Cake\Http\Response|null Redirects to courses index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function elmCourseImport ()
    {
	    // 
	    // elm-courses.csv headers:
	    // "0-Course","1-Course Code","2-Delivery Method","3-Description","4-Learning Environment","5-Learning Group"
	    //
	    //
	    // database schema:
	    //mysql> explain actions;
	    //+-------------------+--------------+------+-----+---------+----------------+
	    //| Field             | Type         | Null | Key | Default | Extra          |
	    //+-------------------+--------------+------+-----+---------+----------------+
	    //| id                | int(11)      | NO   | PRI | NULL    | auto_increment |
	    //| name              | varchar(255) | NO   |     | NULL    |                |
	    //| hyperlink         | varchar(255) | YES  |     | NULL    |                |
	    //| description       | text         | YES  |     | NULL    |                |
	    //| licensing         | text         | YES  |     | NULL    |                |
	    //| moderator_notes   | text         | YES  |     | NULL    |                |
	    //| status            | varchar(100) | YES  |     | NULL    |                |
	    //| meta_title        | varchar(255) | YES  |     | NULL    |                |
	    //| meta_description  | text         | YES  |     | NULL    |                |
	    //| featured          | int(11)      | YES  |     | 0       |                |
	    //| moderation_flag   | int(11)      | YES  |     | 0       |                |
	    //| file_path         | varchar(255) | YES  |     | NULL    |                |
	    //| image_path        | varchar(255) | YES  |     | NULL    |                |
	    //| ministry_id       | int(11)      | YES  | MUL | NULL    |                |
	    //| category_id       | int(11)      | YES  | MUL | NULL    |                |
	    //| method_id         | int(11)      | YES  | MUL | NULL    |                |
	    //| approvedby        | int(11)      | NO   | MUL | NULL    |                |
	    //| created           | datetime     | NO   |     | NULL    |                |
	    //| createdby         | int(11)      | NO   | MUL | NULL    |                |
	    //| modified          | datetime     | NO   |     | NULL    |                |
	    //| modifiedby        | int(11)      | NO   | MUL | NULL    |                |
	    //| action_types_id | int(11)      | YES  | MUL | NULL    |                |
	    //+-------------------+--------------+------+-----+---------+----------------+
	    //22 rows in set (0.00 sec)
	    //
	    $now = date('Y-m-d H:i:s');
	    $desc = '';
	    if (($handle = fopen("/home/allankh/learningagent/webroot/files/GBC_COURSECOUNT.csv", "r")) !== FALSE) {
		fgetcsv($handle);
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			if($data[5] == 'All Government of British Columbia Learners') {
				$desc = '';
				if(isset($data[3]) && $data[3] != '') {
					$desc = utf8_encode($data[3]);
				}
				$action = $this->Actions->newEmptyEntity();
				$action->name = $data[0];
				$action->description = $desc;
				$action->moderator_notes = '';
				$action->licensing = '';
				$action->meta_description = '';
				$action->modifiedby_id = 3;
				$action->createdby_id = 3;
				$action->approvedby_id = 3;
				$action->action_types_id = 1;
				if ($this->Actions->save($action)) {
					// 
				} else {
					echo 'Huh?';
				}
			}
		}
        	return $this->redirect(['action' => 'index']);
	}
    }



    function elmupload () 
    {
	$fileobject = $this->request->getData('file_path');
	$destination = '/home/allankh/learningagent/webroot/files/' . $fileobject->getClientFilename();

	// Existing files with the same name will be replaced.
	$fileobject->moveTo($destination);
        return $this->redirect(['action' => 'elmCourseImport']);
    }
 
}

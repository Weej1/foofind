<?php

class Model_Comments
{

    function  __construct()
    {
            $connection = new Mongo("mongo.users.foofind.com:27017");
             //$connection = new Mongo();
            $db = $connection->foofind;
            $this->collection = $db->comments;
    }



    public function getFileComments($idFile, $lang)
    {

        $filter = array( "_id" => $idFile );    
        $cursor =  $this->collection->find( $filter  ) ;

        //$cursor =  $this->collection->find( $filter  )->sort(  array( "fs"  => -1) )->limit( (int)$limit) ;

         foreach ($cursor as $comment) {
                $comments[] = $comment;
                // var_dump($file['fs])
            }

         return $comments;


    }


    public function getUserComments($username, $limit)
    {
//        $table = new ff_comment();
//        $select = $table->select()->from("ff_comment")
//                ->where("IdUser=?", $idUser)
//                ->order("date desc")
//                ->limit( $limit );
//
//        return $table->fetchAll($select);


        }

 

    public function getCommentVotes($idComment)
    {
        $table = new ff_comment_vote();
        $select = $table->select()
                ->from("ff_comment_vote", "VoteType, count(*) c, sum(karma) k")
                ->where("IdComment=?", $idComment)
                ->group(array("IdComment", "VoteType"));
        return $table->fetchAll($select);
    }

   


    public function deleteCommentVote($idComment, $idUser, $type)
    {
        $table = new ff_comment_vote();
        return $table->delete("IdComment=$idComment and IdUser=$idUser and VoteType=$type");
    }

    public function saveCommentVote(array $data)
    {
        return $this->save(new ff_comment_vote(), $data);
    }

    public function saveComment(array $data)
    {
        //        echo '--------------------------------------------------------------------------------';
        //        var_dump($data);
        //        die();

        //overwrite collection (setted users by default on construct)
        
        $safe_insert = true;
      
        
        return $this->collection->insert($data, $safe_insert);

    }

  
   


}


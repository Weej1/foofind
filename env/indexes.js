db.users.ensureIndex({'email':1});
db.users.ensureIndex({'token':1});
db.users.ensureIndex({'username':1});

db.comment.ensureIndex({'f':1, 'l':1, 'd':1});

db.vote.ensureIndex({'u':1});

db.comment_vote.ensureIndex({'u':1});
db.comment_vote.ensureIndex({'f':1});
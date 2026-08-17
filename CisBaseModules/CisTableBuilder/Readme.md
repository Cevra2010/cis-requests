# CisTableBuilder
###### Table Builder for Laravel with CisFoundation

## Usage

###### Initialize your table
```
$table = CisTableBuilder::table('users');
```
###### Set the CSS class for the table
```
$table->setCssClass('my-css-class');
```
###### Set the data for the table (with pagination and search option)
```
$table->setData(User::class);
```
###### Set the data for the table (without! pagination and search option)
```
$table->setData(User::where('id','>',100));

OR

$myCollection = collect([
    [
    'firstname' => 'Max',
    'lastname' => 'Mustermann',
    ],
    [
    'firstname' => 'Foo',
    'lastname' => 'Bar',
    ]
])
$table->setData($myCollection);

```
###### Set the field names for the table
```
$table->setFields([
    'id' => 'ID',
    'username' => 'Username',
    'created_at' => 'Created at',
 ]);
```

###### Format to datetime format
```
$table->setDateTime('created_at','d.m.Y H:i');
```

###### Set some action links for the the actions row
```
$table->addAction('edit',"user.edit",['id']);
```
###### Set some action by a method
```
$table->addAction('show fullname',"user.show-full-name",['fullname' => 'func:showfullName']);
```
###### Add pagination (only if elequent class is called in "setData")
```
$table->withPagination(12);
```
###### Add search option (only if elequent class is called in "setData")
```
$table->withSearch(['firstname','lastname']);
```

###### In the view
```
<x-cis-table name="users" />
```

###### Full example
```
$table = CisTableBuilder::table('users');
$table->setData(User::class)
    ->setFields([
        'id' => 'ID',
        'firstname' => 'Vorname',
        'lastname' => 'Nachname',
        'email' => 'E-Mail Adresse'
    ])
    ->addAction('ändern','user.show',['id'])
    ->addAction('löschen','user.delete',['id'],'post')
    ->withPagination()
    ->withSearch(['firstname','lastname','email']);
```




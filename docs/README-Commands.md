# BK - Quick Supervision SAAS version Commands

This doc explain the diffrents available command for the BK - Quick Supervision SAAS version.

##Supervision commands:

### Init User Roles // saas:init:roles

This command intitialise the default roles for the platforme ( by role we mean user function ). It can also import role from a csv file.
If a role already exist , it will be updated.

```
php app/console saas:init:roles
```
If a csv file name is passed as argument, the roles will be imported from the csv file.

```
php app/console saas:init:roles fileName
```

P.S: 
- The file name must be passed without extension (.csv)
- The file must be placed in /data/import/saas/
- CSV row data format :  label ; textLabel ; type

### Init User Actions // saas:init:actions

This command intitialise the default actions for the platforme (by action we mean a user right, and action is linked to a role). It can also import actions from a csv file.
If an action already exist , it will be updated.

```
php app/console saas:init:actions
```
If a csv file name is passed as argument, the actions will be imported from the csv file.

```
php app/console saas:init:actions fileName
```

P.S: 
- The file name must be passed without extension (.csv)
- The file must be placed in "/data/import/saas/".
- CSV row data format :  'name' ; 'route' ; 'params' ; 'hasExitBtn' ; 'isPage'; 'type'

### Create First Admin user // saas:init:admin:user

This command intitialise first admin user and affect the admin roles/rights to it.
When the command finish, it will display the Login & Password for the created admin.

```
php app/console saas:init:admin:user
```

### Import Roles/Actions links // saas:import:roles:actions

This command link roles to actions (affect rights to user functions). If the Role or the Action not already exist, it will be created on the fly. 
The links data must be imported from a file passed as argument.

```
php app/console saas:import:roles:actions fileName
```
This command support import from two files type : json and csv.
We can specify the file format by the option --format or -f.

```
php app/console saas:import:roles:actions fileName -f json
```

P.S: 
- The file name must be passed without extension.
- The file must be placed in "/data/import/saas/".
- If no format passed as option, csv will be used as default format.
- CSV row data format :  label ; textLabel ; type ; (actions) : name::route::params::hasExit::isPage,
- In the csv file , actions must be separated by "," and each action attribut is separated by "::" (like the example above). Also , the params attribut follow URL-encoded query string pattern (attribut=value&attribut=value...).

### Init Catagories groups // saas:init:categories:groups

This command intitialise the default categories groups for the platforme . It can also import categories groups from a csv file.
If a category group already exist, it will be skipped.

```
php app/console saas:init:categories:groups
```
If a csv file name is passed as argument, the data will be imported from the csv file.

```
php app/console saas:init:categories:groups fileName 
```

P.S: 
- The file name must be passed without extension (.csv)
- The file must be placed in "/data/import/saas/"
- CSV row data format :   name ;  active  ;  global_id ;  name_translation  ;

### Init Catagories // saas:init:categories

This command intitialise the default categories for the platforme . It can also import categories from a csv file.
If a category already exist, it will be updated. if a category groupe not exist, it will be created on the fly.

```
php app/console saas:init:categories
```
If a csv file name is passed as argument, the data will be imported from the csv file.

```
php app/console saas:init:categories fileName 
```

P.S: 
- The file name must be passed without extension (.csv)
- The file must be placed in "/data/import/saas/"
- CSV row data format :   name ; name_translation ;  active  ;  eligible  ;  order ;   tax_lux  ;   tax_be  ; group_name  ;


### Import Parameters Labels // saas:import:parameters:labels

This command import parameters labels. If the paramter label already exist, it will be skipped. 
The data must be imported from a file passed as argument.

```
php app/console saas:import:parameters:labels fileName
```
This command support import from two files type : json and csv.
We can specify the file format by the option --format or -f.

```
php app/console saas:import:parameters:labels fileName -f json
```

P.S: 
- The file name must be passed without extension.
- The file must be placed in "/data/import/saas/".
- If no format passed as option, csv will be used as default format.
- CSV row data format :  type ; label ; untouchable ; value ; (translations) : local::content,
- In the csv file , transilations must be separated by "," and each translation attribut is separated by "::" (like the example above). Also , the attribut 'value' follow URL-encoded query string pattern (attribut=value&attribut=value...).

### Init payment methods // saas:init:payment_methods

This command intitialise the default payment methods for the platforme . It can also import categories from a csv file.
If a payment method already exist, it will be skipped.
The franchise type (BURGER KING / QUICK) must be indicated by answering the question or by providing it as option for the command. 

```
php app/console saas:init:payment_methods
```
If franchise option is passed (--franchise or -f) , the specified restaurant franchise will be used (no interactive mode).

```
php app/console saas:init:payment_methods -f q
```

P.S: 
- For no interactive mode , possible -f/--franchise option value are : q or quick for Quick franchise / b or burgerking for BurgerKing franchise.

### Import suppliers // saas:import:suppliers

This command intitialise the default suppliers for the platforme . It can also import data from a csv file.
If a supplier already exist, it will be skipped.

```
php app/console saas:import:suppliers
```
If a csv file name is passed as argument, the data will be imported from the csv file.

```
php app/console saas:import:suppliers fileName 
```

P.S: 
- The file name must be passed without extension (.csv)
- The file must be placed in "/data/import/saas/"
- CSV row data format :   name  ;  designation  ;  code  ;  phone  ;  address  ;  active  ;  email  ;  zone  ;

### Import Solding Canals // saas:init:solding:canal

This command intitialise the default Solding Canals for the platforme . It can also import data from a csv file.
If a Solding Canals already exist, it will be skipped.

```
php app/console saas:init:solding:canal
```
If a csv file name is passed as argument, the data will be imported from the csv file.

```
php app/console saas:init:solding:canal fileName 
```

P.S: 
- The file name must be passed without extension (.csv)
- The file must be placed in "/data/import/saas/"
- CSV row data format :    label ;  type  ;  default ;  wyndMappingColumn ;

### Import Restaurants List // saas:import:restaurants:list

This command a restaurants list for the platforme from a the json file passed as argument.
If a restaurant already exist, it will be updated. This command affect also suppliers to restaurants. If a supplier is not found, it will be created on the fly.

```
php app/console saas:import:restaurants:list fileName
```

P.S: 
- The file name must be passed without extension.
- The file must be a valid json file.
- The file must be placed in "/data/import/saas/".

### Import inventory items (purchased products) // saas:import:inventory:items

This command import inventory items (purchased product) from a csv/json file. 
If an inventory item already exist, it will be skipped. 
If an item category is not found , the item will not be added and will be skipped.
If an item supplier is not found, the item will not be added and will be skipped. 
After the items are added successfully, Sync Commands will be created for all imported products to synchronise data with restaurants.

```
php app/console saas:import:inventory:items fileName
```
This command support import from two files type : json and csv.
We can specify the file format by the option --format or -f.

```
php app/console saas:import:inventory:items -f json
```

P.S: 
- The file name must be passed without extension.
- The file must be placed in "/data/import/saas/".
- If no format passed as option, csv will be used as default format.
- CSV row data format :  name ; name_translation ; global_product_id ; reference ; active ; external_id ; status ; type ; storage_condition ; buying_cost ; label_unit_exped ; label_unit_inventory ; label_unit_usage ; inventory_qty ; usage_qty ; id_item_inv ; dls ; category_name ; (suppliers) ; (restaurants)
- In the csv file , restaurants is a list of restaurant code separated by ",". Same for suppliers. 

### Import sold items (products sold) // saas:import:sold:products

This command import sold items (products sold) from a csv/json file. 
If an product sold already exist, it will be skipped. 
If a product sold recipe is not found, it we created on the fly.
After the items are added successfully, Sync Commands will be created for all imported products to synchronise data with restaurants.

```
php app/console saas:import:sold:products fileName
```
This command support import from two files type : json and csv.
We can specify the file format by the option --format or -f.

```
php app/console saas:import:sold:products -f json
```

P.S: 
- The file name must be passed without extension.
- The file must be placed in "/data/import/saas/".
- If no format passed as option, csv will be used as default format.
- CSV row data format :  name ;  reference ; active ; globalProductID ; lastDateSynchro ; dateSynchro ; id ; createdAtInCentral ; updatedAtInCentral ; type ; codePlu ; externalId ; product_discr ; name_translation ; productPurchasedName ; productPurchasedCode ; (recipes) ; (restaurants) ;
- In the csv file , restaurants is a list of restaurant code separated by ",". 
- In the csv file , recipes are in the followinf format : [ externalId,active,revenuePrice,{{soldingCanal}},{{recipeLines}} ]
- In the csv file , {{soldingCanal}} is formated like this : label::type::wyndMppingColumn::default.
- In the csv file , {{recipeLines}} is formated like this : qty::supplierCode::productPurchasedName::productPurchasedCode and diffrents recipe line are separated by |.
 
### Init All // saas:init:all

This command intitialise all default platforme parameters and data. It execute all the above commands in the same order.
The franchise type (BURGER KING / QUICK) must be indicated by answering the question or by providing it as option for the command. 

```
php app/console saas:init:all
```
If franchise option is passed (--franchise or -f) , the specified restaurant franchise will be used (no interactive mode).

```
php app/console saas:init:all -f q
```

P.S: 
- For no interactive mode , possible -f/--franchise option value are : q or quick for Quick franchise / b or burgerking for BurgerKing franchise.

### Import central users list // saas:import:central:users

This command import a central users list from a csv/json file. 
If a user already exist, it will be skipped. 

```
php app/console saas:import:central:users fileName
```
This command support import from two files type : json and csv.
We can specify the file format by the option --format or -f.

```
php app/console saas:import:central:users -f json
```

P.S: 
- The file name must be passed without extension.
- The file must be placed in "/data/import/saas/".
- If no format passed as option, csv will be used as default format.
- CSV row data format : lastName ; firstName ; login ; password ; email ; (eligibleRestaurants) ; (roles)
- In the csv file , eligibleRestaurants is a list of restaurant code separated by ",". Roles is roles list separated by "," . 

##BO commands:

### Import single restaurant and its parameters // saas:import:restaurant

This command import a single restaurant instance and its paramters (an exported restaurant from a BO instance) from a json file.
it take as argument the restaurant code.
If the restaurant already exist, it will be updated.

```
php app/console saas:import:restaurant XXXX
```
Where XXXX is the restaurant code.
P.S: 
- The file name must follow this pattern: restaurant_xxxx.json.
- The file must be placed in "/data/import/saas/".
- The file must be exported from a BO instance.

This command set the following restaurant data :
- Restaurant basic informations.
- Restaurant suppliers ( if a supplier is not found, it will be created on the fly).
- Restaurant suppliers plannings.
- Restaurant optikitchen parameters.
- Restaurant payment methods parameters.
- Restaurant additional emails.
- Restaurant Users.
- Restaurant parameters : Orders Api Url ; Users Api Url ; POS status ; Secret Key ; Cashboxes number ; Start Day Funds ; Opening Hour ; Closing Hour ; EFT status ; 


### Import BO Historic Stock data // saas:import:bo:historic:data

This command import a single BO restaurant instance historic stock data (exported from a BO instance) from a json file.
It take as argument the restaurant code.
This command must be executed before the import of stock data to link the entities correctly with the historic data.

```
php app/console saas:import:bo:historic:data XXXX
```
Where XXXX is the restaurant code.
P.S: 
- The file name must follow this pattern: historicData_restaurant_xxxx.json.
- The file must be placed in "/data/import/saas/".
- The file must be exported from a BO instance.

Imported historic data :
- ProductPurchased Historic
- ProductSold Historic

### Import BO Stock data // saas:import:bo:stock:data

This command import a single BO restaurant instance stock data (exported from a BO instance) from a json file.
It take as argument the restaurant code.

```
php app/console saas:import:bo:stock:data XXXX
```
Where XXXX is the restaurant code.
P.S: 
- The file name must follow this pattern: stockData_restaurant_xxxx.json.
- The file must be placed in "/data/import/saas/".
- The file must be exported from a BO instance.

Imported stock data :
- Sheet Model.
- Inventory Sheet
- Loss Sheet

### Import BO Purchase data // saas:import:bo:purchase:data

This command import a single BO restaurant instance purchase data (exported from a BO instance) from a json file.
It take as argument the restaurant code.

```
php app/console saas:import:bo:purchase:data XXXX
```
Where XXXX is the restaurant code.
P.S: 
- The file name must follow this pattern: purchaseData_restaurant_xxxx.json.
- The file must be placed in "/data/import/saas/".
- The file must be exported from a BO instance.

Imported purchase data :
- Deliveries
- Orders
- Transfers
- Returns
- CoefBases

### Import BO tickets data // saas:import:bo:tickets:data

This command import a single BO restaurant instance tickets data (exported from a BO instance) from csv files.
It take as argument the restaurant code.

```
php app/console saas:import:bo:tickets:data XXXX
```
Where XXXX is the restaurant code.
P.S: 
- The tickets data are spearated in 4 files, so the import process need all those file in the same time.
- The files names must follow those patterns : tickets_restaurant_xxxx.csv ; ticketsPayments_restaurant_xxxx.csv ; ticketsInterventions_restaurant_xxxx.csv ; ticketsInterventionsSub_restaurant_xxxx.csv ; ticketsLines_restaurant_xxxx.csv
- The files must be placed in "/data/import/saas/".
- The files must be exported from a BO instance.
->this command take a long time to finish.

Imported purchase data :
- Tickets ( including tickets lines, interventions and interventionsSub)
- Tickets Payment

### Import BO Financial data // saas:import:bo:financial:data

This command import a single BO restaurant instance financial data (exported from a BO instance) from a json file.
It take as argument the restaurant code.

```
php app/console saas:import:bo:financial:data XXXX
```
Where XXXX is the restaurant code.
P.S: 
- The file name must follow this pattern: financialData_restaurant_xxxx.json.
- The file must be placed in "/data/import/saas/".
- The file must be exported from a BO instance.

Imported purchase data :
- Withdrawals
- Envelopes
- Expenses
- Deposits
- Cashbox Bank Card Containers
- Cashbox CheckQuick Containers
- Cashbox Check Restaurant Containers
- Cashbox Foreign Currency Containers
- Cashbox Meal Ticket Containers
- Cashbox RealCash Containers
- Cashbox Cashbox Discount Containers
- Cashbox Counts
- Chest Small Chests
- Chest Cashbox Funds
- Chest Exchange Funds
- Chest Counts


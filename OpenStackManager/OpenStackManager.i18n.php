<?php
/**
 * Internationalisation file for extension OpenStackManager
 *
 * @file
 * @ingroup Extensions
 * @author Ryan Lane <rlane@wikimedia.org>
 * @copyright © 2010 Ryan Lane
 * @license GNU General Public Licence 2.0 or later
 */

$messages = array();

/** English
 * @author Ryan Lane <rlane@wikimedia.org>
 * @author Sam Reed <reedy@wikimedia.org>
 */
$messages['en'] = array(
	'openstackmanager-desc' => 'Lets users manage Nova and Swift.',

	'openstackmanager' => 'OpenStackManager',
	'openstackmanager-instance' => 'Manage instance',
	'openstackmanager-title' => 'OpenStackManager',

	'specialpages-group-nova' => 'OpenStack Nova',
	'novaaddress' => 'Manage public IP addresses',
	'novadomain' => 'Manage DNS domains',
	'novainstance' => 'Manage instances',
	'novakey' => 'Manage your public SSH keys',
	'novaproject' => 'Manage OpenStack projects',
	'novasecuritygroup' => 'Manage firewall security groups',
	'novarole' => 'Manage global roles',

	'openstackmanager-novadomain' => 'Nova domain',
	'openstackmanager-novainstance' => 'Nova instance',
	'openstackmanager-novakey' => 'Nova key',
	'openstackmanager-novaproject' => 'Nova project',

	'openstackmanager-delete' => 'delete',
	'openstackmanager-configure' => 'configure',
	'openstackmanager-rename' => 'rename',
	'openstackmanager-actions' => 'Actions',
	'openstackmanager-notloggedin' => 'Login required',
	'openstackmanager-mustbeloggedin' => 'You must be logged in to perform this action.',
	'openstackmanager-nonovacred' => 'No Nova credentials found for your account.',
	'openstackmanager-nonovacred-admincreate' => 'There were no Nova credentials found for your user account. Please ask a Nova administrator to create credentials for you.',
	'openstackmanager-noaccount' => 'Your account is not in the project requested.',
	'openstackmanager-noaccount2' => 'You can not complete the action requested as your user account is not in the project requested.',
	'openstackmanager-createdomain' => 'Create domain',

	'openstackmanager-deletedomain' => 'Delete domain',
	'openstackmanager-deletedomain-confirm' => 'Are you sure you wish to delete domain "$1"? This action has reprecusions on all VMs. Do not take this action lightly!',
	'openstackmanager-novadomain-domain' => 'Domain',
	'openstackmanager-novadomain-info' => 'Domain information',

	'openstackmanager-createdomainfailed' => 'Failed to create domain.',
	'openstackmanager-createddomain' => 'Created domain.',
	'openstackmanager-domainlist' => 'Domain list',
	'openstackmanager-backdomainlist' => 'Back to domain list',
	'openstackmanager-deleteddomain' => 'Successfully deleted domain.',
	'openstackmanager-failedeletedomain' => 'Failed to delete domain.',
	'openstackmanager-domainname' => 'Domain name',
	'openstackmanager-fqdn' => 'Fully qualified domain name',
	'openstackmanager-location' => 'Location',
	'openstackmanager-location-help' => 'Location field is for private DNS zones. Leave blank for public zones.',

	'openstackmanager-novainstance-instance' => 'Instance',
	'openstackmanager-configureinstance' => 'Configure instance',
	'openstackmanager-nonexistanthost' => 'The host requested does not exist.',
	'openstackmanager-dnsdomain' => 'DNS domain',
	'openstackmanager-puppetclasses' => 'Puppet classes',
	'openstackmanager-novainstance-info' => 'Instance information',
	'openstackmanager-novainstance-puppetinfo' => 'Puppet information',

	'openstackmanager-deleteinstancequestion' => 'Are you sure you wish to delete instance "$1"?',
	'openstackmanager-instancelist' => 'Instance list',
	'openstackmanager-instancename' => 'Instance name',
	'openstackmanager-instanceid' => 'Instance ID',
	'openstackmanager-instancestate' => 'Instance state',
	'openstackmanager-instancetype' => 'Instance type',
	'openstackmanager-instanceip' => 'Instance IP address',
	'openstackmanager-instancepublicip' => 'Instance floating IP address',
	'openstackmanager-securitygroups' => 'Security groups',
	'openstackmanager-availabilityzone' => 'Availability zone',
	'openstackmanager-imageid' => 'Image ID',
	'openstackmanager-imagetype' => 'Image type',
	'openstackmanager-launchtime' => 'Launch time',

	'openstackmanager-createinstance' => 'Create a new instance',
	'openstackmanager-invaliddomain' => 'Requested domain is invalid.',

	'openstackmanager-createdinstance' => 'Created instance $1 with image $2 and hostname $3.',
	'openstackmanager-createfailedldap' => 'Failed to create instance as the host could not be added to LDAP.',
	'openstackmanager-createinstancefailed' => 'Failed to create instance.',
	'openstackmanager-backinstancelist' => 'Back to instance list',
	'openstackmanager-deletedinstance' => 'Deleted instance $1',
	'openstackmanager-deletedinstance-faileddns' => 'Successfully deleted instance, but failed to remove $1 DNS entry.',
	'openstackmanager-modifiedinstance' => 'Successfully modified instance.',
	'openstackmanager-modifyinstancefailed' => 'Failed to modify instance.',
	'openstackmanager-deleteinstancefailed' => 'Failed to delete instance.',

	'openstackmanager-novapublickey' => 'Public SSH key',
	'openstackmanager-novakey-key' => 'Public SSH key',
	'openstackmanager-novakey-info' => 'Public SSH key info',
	'openstackmanager-deletekey' => 'Delete key',
	'openstackmanager-deletekeyconfirm' => 'Are you sure you wish to delete the above key?',
	'openstackmanager-keylist' => 'Key list',
	'openstackmanager-importkey' => 'Import a new key',
	'openstackmanager-name' => 'Name',
	'openstackmanager-fingerprint' => 'Fingerprint',
	'openstackmanager-invalidkeypair' => 'Invalid keypair location configured.',
	'openstackmanager-keypairimportfailed' => 'Failed to import keypair.',
	'openstackmanager-keypairimported' => 'Imported keypair.',
	'openstackmanager-keypairimportedfingerprint' => 'Imported keypair $1 with fingerprint $2.',
	'openstackmanager-backkeylist' => 'Back to key list',
	'openstackmanager-deletedkey' => 'Successfully deleted key.',
	'openstackmanager-deletedkeyfailed' => 'Failed to delete key.',

	'openstackmanager-addmember' => 'Add project member',
	'openstackmanager-removemember' => 'Remove project member',
	'openstackmanager-removeprojectconfirm' => 'Are you sure you wish to delete project "$1"? This action has reprecusions on all VMs. Do not take this action lightly!',
	'openstackmanager-createproject' => 'Create a new project',
	'openstackmanager-projectname' => 'Project name',
	'openstackmanager-members' => 'Members',
	'openstackmanager-member' => 'Member',
	'openstackmanager-action' => 'Action',
	'openstackmanager-createprojectfailed' => 'Failed to create project.',
	'openstackmanager-createdproject' => 'Created project.',
	'openstackmanager-badprojectname' => 'Bad project name provided. Project names start with a-z, and can only contain a-z, 0-9, -, and _ characters.',
	'openstackmanager-projectlist' => 'Project list',
	'openstackmanager-backprojectlist' => 'Back to project list',
	'openstackmanager-deleteproject' => 'Delete project',
	'openstackmanager-deletedproject' => 'Successfully deleted project.',
	'openstackmanager-deleteprojectfailed' => 'Failed to delete project.',
	'openstackmanager-addedto' => 'Successfully added $1 to $2.',
	'openstackmanager-failedtoadd' => 'Failed to add $1 to $2.',
	'openstackmanager-removedfrom' => 'Successfully removed $1 from $2.',
	'openstackmanager-failedtoremove' => 'Failed to remove $1 from $2.',
	'openstackmanager-badinstancename' => 'Bad instance name provided. Instance names must start with a-z, and can only contain a-z, 0-9, and - characters.',
	'openstackmanager-novaproject-project' => 'Project',
	'openstackmanager-novaproject-info' => 'Project information',

	'openstackmanager-roles' => 'Roles',
	'openstackmanager-rolename' => 'Role name',
	'openstackmanager-removerolemember' => 'Remove role member',
	'openstackmanager-addrolemember' => 'Add role member',
	'openstackmanager-rolelist' => 'Global role list',
	'openstackmanager-nomemberstoadd' => 'There are no members to add to this group.',
	'openstackmanager-nomemberstoremove' => 'There are no members to remove from this group.',
	'openstackmanager-novarole-role' => 'Nova role',
	'openstackmanager-novarole-info' => 'Nova role information',

	'openstackmanager-shellaccountname' => 'Instance shell account name',
	'openstackmanager-shellaccountnamehelp' => 'The shell account name must start with a-z, and can only contain a-z, 0-9, -, and _ characters.',

	'openstackmanager-addresslist' => 'Public IP address list',
	'openstackmanager-address' => 'Public IP address',
	'openstackmanager-allocateaddress' => 'Allocate a new public IP address',
	'openstackmanager-releaseaddress' => 'Release IP address',
	'openstackmanager-associateaddress' => 'Associate IP address',
	'openstackmanager-reassociateaddress' => 'Reassociate IP address',
	'openstackmanager-disassociateaddress' => 'Disassociate IP address',
	'openstackmanager-allocateaddressfailed' => 'Failed to allocate new public IP address.',
	'openstackmanager-allocatedaddress' => 'Allocated new public IP address: $1',
	'openstackmanager-backaddresslist' => 'Back to address list',
	'openstackmanager-allocateaddress-confirm' => 'Are you sure you would like to allocate a new public IP address in project $1?',
	'openstackmanager-releasedaddress' => 'Successfully released address: $1',
	'openstackmanager-releaseaddressfailed' => 'Failed to release address: $1',
	'openstackmanager-cannotreleaseaddress' => 'Cannot release address that has DNS entries or is associated with an instance. Please remove all host entries and disassociate the IP address before trying to release it.',
	'openstackmanager-associatedaddress' => 'Successfully associated $1 with instance ID $2.',
	'openstackmanager-associateaddressfailed' => 'Failed to associate $1 with instance ID $2.',
	'openstackmanager-disassociatedaddress' => 'Successfully disassociated $1.',
	'openstackmanager-disassociateaddressfailed' => 'Failed to disassociate $1.',
	'openstackmanager-disassociateaddress-confirm' => 'Are you sure you would like to disassociate $1?',
	'openstackmanager-releaseaddress-confirm' => 'Are you sure you would like to release $1?',
	'openstackmanager-invalidaddress' => '$1 is not a valid allocated IP address.',
	'openstackmanager-invalidaddressforproject' => '$1 is not in the project requested.',
	'openstackmanager-addedhost' => 'Successfully added $1 entry for IP address $2.',
	'openstackmanager-addhostfailed' => 'Failed to add $1 entry for IP address $2.',
	'openstackmanager-hostname' => 'Host name',
	'openstackmanager-hostnames' => 'Host names',
	'openstackmanager-addhost' => 'Add host name',
	'openstackmanager-removehost' => 'Remove host',
	'openstackmanager-removehost-action' => '(Remove host name)',
	'openstackmanager-removehost-confirm' => 'Are you sure you would like to remove host $1 from $2?',
	'openstackmanager-removedhost' => 'Successfully removed $1.',
	'openstackmanager-removehostfailed' => 'Failed to remove $1.',
	'openstackmanager-nonexistenthost' => 'The host requested does not exist.',

	'openstackmanager-needsysadminrole' => 'Sysadmin role required',
	'openstackmanager-needsysadminrole2' => 'You must be a member of the sysadmin role to perform this action.',
	'openstackmanager-neednetadminrole' => 'Netadmin role required',
	'openstackmanager-neednetadminrole2' => 'You must be a member of the netadmin role to perform this action.',

	'openstackmanager-createsecuritygroup' => 'Create security group',
	'openstackmanager-securitygroupname' => 'Security group name',
	'openstackmanager-securitygroupdescription' => 'Description',
	'openstackmanager-configuresecuritygroup' => 'Configure security group',
	'openstackmanager-deletesecuritygroup' => 'Delete security group',
	'openstackmanager-deletesecuritygroup-confirm' => 'Are you sure you would like to delete $1?',
	'openstackmanager-securitygrouplist' => 'Security group list',
	'openstackmanager-securitygrouprule' => 'Rules',
	'openstackmanager-securitygrouprule-toport' => 'To port',
	'openstackmanager-securitygrouprule-fromport' => 'From port',
	'openstackmanager-securitygrouprule-protocol' => 'Protocol',
	'openstackmanager-securitygrouprule-ipranges' => 'CIDR ranges',
	'openstackmanager-securitygrouprule-groups' => 'Source group',
	'openstackmanager-createnewsecuritygroup' => 'Create a new security group',
	'openstackmanager-addrule-action' => 'add rule',
	'openstackmanager-removerule-action' => 'remove rule',
	'openstackmanager-modifiedgroup' => 'Successfully modified security group.',
	'openstackmanager-modifygroupfailed' => 'Failed to modify security group.',
	'openstackmanager-nonexistantgroup' => 'The security group requested does not exist.',
	'openstackmanager-backsecuritygrouplist' => 'Back to the security group list',
	'openstackmanager-createdsecuritygroup' => 'Successfully created security group.',
	'openstackmanager-createsecuritygroupfailed' => 'Failed to create security group.',
	'openstackmanager-nonexistantsecuritygroup' => 'The security group you are trying to delete does not exist.',
	'openstackmanager-deletedsecuritygroup' => 'Successfully deleted security group.',
	'openstackmanager-deletesecuritygroupfailed' => 'Failed to delete security group.',
	'openstackmanager-removerule' => 'Remove rule',
	'openstackmanager-removerule-confirm' => 'Are you sure you would like to remove this rule from $1?',
	'openstackmanager-removedrule' => 'Successfully removed rule.',
	'openstackmanager-removerulefailed' => 'Failed to remove rule.',
	'openstackmanager-addrule' => 'Add rule',
	'openstackmanager-securitygrouprule-fromport' => 'From port',
	'openstackmanager-securitygrouprule-toport' => 'To port',
	'openstackmanager-securitygrouprule-protocol' => 'Protocol',
	'openstackmanager-securitygrouprule-ranges' => 'CIDR ranges',
	'openstackmanager-securitygrouprule-groups' => 'Security groups',
	'openstackmanager-securitygrouprule-ranges-help' => 'CIDR ranges is a comma separated list of ranges.',
	'openstackmanager-securitygrouprule-groups-help' => 'Instances in added security groups will be allowed ingress of all ports and protocols.',
	'openstackmanager-addedrule' => 'Successfully added rule.',
	'openstackmanager-addrulefailed' => 'Failed to add rule.',

	'openstackmanager-email-subject' => 'Your instance is ready to be logged into.',
	'openstackmanager-email-body' => 'The following instance has been created, and is ready to be logged into: ',
	'right-manageproject' => 'Manage Openstack projects and roles',
);

/** Message documentation (Message documentation)
 * @author Sam Reed <reedy@wikimedia.org>
 */
$messages['qqq'] = array(
	'openstackmanager-desc' => '{{desc}}',
	'openstackmanager-notloggedin' => 'Page title',
	'openstackmanager-createproject' => 'Page title',
	'openstackmanager-addmember' => 'Page title',
	'openstackmanager-removemember' => 'Page title',
	'openstackmanager-deleteproject' => 'Page title',
	'openstackmanager-projectlist' => 'Page title',
	'openstackmanager-instancelist' => 'Page title',
	'openstackmanager-deletedomain' => 'Page title',
	'openstackmanager-createdomain' => 'Page title',
	'openstackmanager-configureinstance' => 'Page title',
	'openstackmanager-importkey' => 'Page title',
	'openstackmanager-deletekey' => 'Page title',
	'openstackmanager-keylist' => 'Page title',
	'openstackmanager-nonovacred' => 'Page title',
);


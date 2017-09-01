# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below. The "2" in Vagrant.configure
# configures the configuration version (we support older styles for
# backwards compatibility). Please don't change it unless you know what
# you're doing.
Vagrant.configure("2") do |config|
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. You can search for
  # boxes at https://vagrantcloud.com/search.
  config.vm.box = 'ubuntu/xenial64'

  config.ssh.insert_key = true
  config.ssh.forward_agent = true

  config.vm.provider 'virtualbox' do |vb|
    vb.name = 'twinepm-server-heroku'
  end

  config.vm.define 'twinepm-server-heroku'

  config.vm.network 'forwarded_port', guest: 443, host: 8000

  defaultBranch = ENV['TWINEPM_DEFAULT_BRANCH'] || "master"
  branch = ENV['TWINEPM_BRANCH'] || defaultBranch
  repoName = 'twinepm-server-heroku'
  shellStr =
    "TWINEPM_BRANCH=#{branch} && " +
    'export TWINEPM_BRANCH && ' +
    'cd /etc && ' +
    "git clone -b #{branch} https://github.com/furkle/#{repoName} && " +
    "cd #{repoName} && " +
    './scripts/getPhing &&' +
    'phing get-vm-dependencies && ' +
    'phing build-containers && ' +
    'phing run-containers && ' +
    'docker exec -i twinepm_logic "cd /etc/twinepm-server-heroku && ' +
      'phing install-php-packages"'

  # Enable provisioning with a shell script. Additional provisioners such as
  # Puppet, Chef, Ansible, Salt, and Docker are also available. Please see the
  # documentation for more information about their specific syntax and use.
  config.vm.provision "shell", inline: shellStr
end
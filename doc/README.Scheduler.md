Scheduler
==========

The scheduler in SeedDMS manages frequently run tasks. It is very similar
to regular unix cron jobs. A task in SeedDMS is an instanciation of a task
class which itself is defined by an extension. SeedDMS has some predefined
classes e.g. core::expireddocs.

In order for tasks to be runnalbe a user cli_scheduler must exists.

php in server may not have the same extensions as the php cli. This can cause
some extensions to be disable and consequently some task classes are not defined.

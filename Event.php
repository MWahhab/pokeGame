<?php

class Event implements JsonSerializable
{
    /**
     * @var int   $error Refers to the error code
     */
    private int   $error;

    /**
     * @var array $events Refers to the events that have taken place
     */
    private array $events;

    /**
     * Upon instantiation, sets the events property to an empty array
     */
    public function __construct()
    {
        $this->events = [];
    }

    /**
     * @return int Retrieves the error code
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * @param  int  $error Refers to the error code
     * @return void        Sets the error code
     */
    public function setError(int $error): void
    {
        $this->error = $error;
    }

    /**
     * @return array Retrieves the events array
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @param  array $events Refers to the array being set
     * @return void          Sets the events array
     */
    public function setEvents(array $events): void
    {
        $this->events = $events;
    }

    /**
     * @param  string $event Refers to the event being added
     * @return void          Adds an event to the list of events
     */
    public function addEvent(string $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return mixed This specifies the data that should be serialized to JSOn
     */
    #[\Override] public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
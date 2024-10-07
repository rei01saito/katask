import { Button, Link } from '@nextui-org/react'

type User = {
  name: string
}

export interface HeaderProps {
  user?: User
  onLogin?: () => void
  onLogout?: () => void
  onCreateAccount?: () => void
}

export const Header = ({ user }: HeaderProps) => (
  <header className="mb-8">
    <div className="flex items-center justify-between py-4 px-5 border-b-2 border-slate-100">
      <Link href={'/'}>
        <svg
          width="32"
          height="32"
          viewBox="0 0 32 32"
          xmlns="http://www.w3.org/2000/svg"
          className="inline-block align-top"
        >
          <g fill="none" fillRule="evenodd">
            <path
              d="M10 0h12a10 10 0 0110 10v12a10 10 0 01-10 10H10A10 10 0 010 22V10A10 10 0 0110 0z"
              fill="#FFF"
            />
            <path
              d="M5.3 10.6l10.4 6v11.1l-10.4-6v-11zm11.4-6.2l9.7 5.5-9.7 5.6V4.4z"
              fill="#555AB9"
            />
            <path
              d="M27.2 10.6v11.2l-10.5 6V16.5l10.5-6zM15.7 4.4v11L6 10l9.7-5.5z"
              fill="#91BAF8"
            />
          </g>
        </svg>
        <h1 className="inline-block mx-2 mb-3 align-top leading-1 font-bold">
          Katask
        </h1>
      </Link>
      <div>
        {user ? (
          <div>
            <span className="welcome">
              Welcome, <b>{user.name}</b>!
            </span>
            <Link href={'/logout'}>
              <Button size="sm">Logout</Button>
            </Link>
          </div>
        ) : (
          <div className="flex items-center">
            <Button size="sm" className="mx-1" as={Link} href="/login">
              Login
            </Button>
            <Button
              size="sm"
              color="primary"
              className="mx-1"
              as={Link}
              href="/sign-in"
            >
              Sign in
            </Button>
          </div>
        )}
      </div>
    </div>
  </header>
)
